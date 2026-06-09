<?php

namespace App\Services\WhatsApp;

use App\Models\Contact;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

/**
 * Wrapper della Meta WhatsApp Cloud API, scoped a un singolo tenant
 * (ADR-003: ogni tenant ha il proprio phone_number_id + access_token).
 *
 * Ogni invio viene loggato sulla tabella `messages` (task 2.1.6): la riga
 * nasce `queued`, diventa `sent` con il `meta_message_id` in caso di successo,
 * o `failed` con il dettaglio errore Meta.
 */
class WhatsAppService
{
    /** Tentativi totali (1 + retry) sugli errori transitori. */
    private const MAX_TRIES = 3;

    public function __construct(private readonly Tenant $tenant) {}

    public static function for(Tenant $tenant): self
    {
        return new self($tenant);
    }

    /**
     * Invia un template approvato (task 2.1.2). Unico tipo consentito fuori
     * dalla finestra 24h.
     *
     * @param  array<int, array<string, mixed>>  $components  componenti header/body/button con i parametri
     */
    public function sendTemplate(
        Contact|string $to,
        string $template,
        string $language = 'it',
        array $components = [],
    ): Message {
        $payload = [
            'type' => 'template',
            'template' => array_filter([
                'name' => $template,
                'language' => ['code' => $language],
                'components' => $components ?: null,
            ]),
        ];

        return $this->dispatch($to, Message::TYPE_TEMPLATE, $payload, [
            'template' => $template,
            'language' => $language,
            'components' => $components,
        ]);
    }

    /**
     * Invia un messaggio di testo semplice (task 2.1.3).
     *
     * NOTA: consentito solo dentro la finestra di servizio 24h. Fuori finestra
     * la Meta API risponde con errore 131047 → {@see WhatsAppApiException}.
     */
    public function sendText(Contact|string $to, string $body, bool $previewUrl = false): Message
    {
        $payload = [
            'type' => 'text',
            'text' => ['preview_url' => $previewUrl, 'body' => $body],
        ];

        return $this->dispatch($to, Message::TYPE_TEXT, $payload, ['body' => $body]);
    }

    /**
     * Messaggio interattivo con reply button (max 3 — task 2.1.4).
     *
     * @param  array<int, array{id: string, title: string}>  $buttons
     */
    public function sendButtons(
        Contact|string $to,
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): Message {
        if (count($buttons) < 1 || count($buttons) > 3) {
            throw new InvalidArgumentException('WhatsApp consente da 1 a 3 reply button.');
        }

        $interactive = array_filter([
            'type' => 'button',
            'header' => $header ? ['type' => 'text', 'text' => $header] : null,
            'body' => ['text' => $body],
            'footer' => $footer ? ['text' => $footer] : null,
            'action' => [
                'buttons' => array_map(fn (array $b) => [
                    'type' => 'reply',
                    'reply' => ['id' => $b['id'], 'title' => $b['title']],
                ], array_values($buttons)),
            ],
        ]);

        return $this->dispatch($to, Message::TYPE_INTERACTIVE, [
            'type' => 'interactive',
            'interactive' => $interactive,
        ], ['interactive' => 'button', 'body' => $body, 'buttons' => $buttons]);
    }

    /**
     * Messaggio interattivo con lista (max 10 righe totali — task 2.1.4).
     *
     * @param  array<int, array{title?: string, rows: array<int, array{id: string, title: string, description?: string}>}>  $sections
     */
    public function sendList(
        Contact|string $to,
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): Message {
        $rowCount = array_sum(array_map(fn (array $s) => count($s['rows'] ?? []), $sections));
        if ($rowCount < 1 || $rowCount > 10) {
            throw new InvalidArgumentException('Una lista WhatsApp consente da 1 a 10 righe totali.');
        }

        $interactive = array_filter([
            'type' => 'list',
            'header' => $header ? ['type' => 'text', 'text' => $header] : null,
            'body' => ['text' => $body],
            'footer' => $footer ? ['text' => $footer] : null,
            'action' => [
                'button' => $buttonText,
                'sections' => array_values($sections),
            ],
        ]);

        return $this->dispatch($to, Message::TYPE_INTERACTIVE, [
            'type' => 'interactive',
            'interactive' => $interactive,
        ], ['interactive' => 'list', 'body' => $body, 'sections' => $sections]);
    }

    /**
     * Cuore dell'invio: logga, chiama l'API con retry, aggiorna lo stato.
     *
     * @param  array<string, mixed>  $apiPayload  porzione specifica del payload Meta
     * @param  array<string, mixed>  $logContent  rappresentazione normalizzata per la colonna `content`
     */
    private function dispatch(Contact|string $to, string $type, array $apiPayload, array $logContent): Message
    {
        $contact = $this->resolveContact($to);

        $message = $this->tenant->messages()->create([
            'contact_id' => $contact->id,
            'direction' => Message::DIRECTION_OUTBOUND,
            'type' => $type,
            'content' => $logContent,
            'status' => Message::STATUS_QUEUED,
        ]);

        $body = array_merge([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $contact->phone,
        ], $apiPayload);

        try {
            $response = $this->http()->post($this->endpoint(), $body);
        } catch (ConnectionException $e) {
            $message->update([
                'status' => Message::STATUS_FAILED,
                'error' => ['type' => 'connection', 'message' => $e->getMessage()],
            ]);

            throw new WhatsAppApiException(
                "Meta Cloud API irraggiungibile: {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($response->failed()) {
            /** @var array<string, mixed> $error */
            $error = $response->json('error') ?? ['message' => $response->body()];
            $message->update(['status' => Message::STATUS_FAILED, 'error' => $error]);

            throw WhatsAppApiException::fromMetaError($error, $response->status());
        }

        $message->update([
            'status' => Message::STATUS_SENT,
            'meta_message_id' => $response->json('messages.0.id'),
        ]);

        return $message->refresh();
    }

    /**
     * Client HTTP autenticato col token del tenant, con retry+backoff (task 2.1.5)
     * limitato ai soli errori transitori (rate limit, 5xx, connessione).
     */
    private function http(): PendingRequest
    {
        return Http::withToken($this->tenant->access_token)
            ->acceptJson()
            ->asJson()
            ->retry(
                self::MAX_TRIES,
                fn (int $attempt) => $attempt * 250, // backoff: 250ms, 500ms
                fn (Throwable $e) => $this->isTransient($e),
                throw: false,
            );
    }

    private function isTransient(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof RequestException && $e->response) {
            $status = $e->response->status();

            return $status === 429 || $status >= 500;
        }

        return false;
    }

    private function endpoint(): string
    {
        $version = config('services.meta.graph_version');

        return "https://graph.facebook.com/{$version}/{$this->tenant->phone_number_id}/messages";
    }

    private function resolveContact(Contact|string $to): Contact
    {
        if ($to instanceof Contact) {
            return $to;
        }

        return $this->tenant->contacts()->firstOrCreate(
            ['phone' => $this->normalizePhone($to)],
        );
    }

    /** Normalizza a E.164 senza `+` (formato richiesto dalla Cloud API). */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
