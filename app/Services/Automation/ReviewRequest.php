<?php

namespace App\Services\Automation;

use App\Models\Appointment;
use App\Models\Automation;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\WhatsApp\WhatsAppApiException;
use App\Services\WhatsApp\WhatsAppService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Richiesta Recensione (E3.3): qualche ora dopo un appuntamento `completed`
 * invia al contatto un template con il link alle recensioni Google (task 3.3.2).
 *
 * Scoped per-tenant come {@see WhatsAppService}; si attiva solo se il tenant ha
 * un'Automation `review_request` attiva. Il template è MARKETING, quindi parte
 * solo verso contatti con opt-in. Due garanzie contro lo spam:
 *  - per-appuntamento: `appointments.review_requested` viene marcato una sola volta;
 *  - per-contatto: non più di una richiesta ogni N giorni (task 3.3.4).
 */
class ReviewRequest
{
    public const DEFAULT_TEMPLATE = 'review_request';

    /** Ore da attendere dopo l'appuntamento prima di chiedere la recensione. */
    public const DEFAULT_DELAY_HOURS = 24;

    /** Finestra di rate limiting per-contatto (task 3.3.4). */
    public const DEFAULT_RATE_LIMIT_DAYS = 30;

    public function __construct(private readonly Tenant $tenant) {}

    public static function for(Tenant $tenant): self
    {
        return new self($tenant);
    }

    /**
     * Trova gli appuntamenti completati pronti per la richiesta recensione e li
     * invia (task 3.3.2). Idempotente: ogni appuntamento è valutato una sola
     * volta (`review_requested` viene marcato anche quando saltiamo per rate limit,
     * così non lo riconsideriamo a ogni giro).
     *
     * @return int numero di richieste effettivamente inviate
     */
    public function dispatchDue(?CarbonInterface $now = null): int
    {
        $automation = $this->automation();

        if (! $automation) {
            return 0;
        }

        $now = $now ? $now->copy() : now();
        $config = $automation->config ?? [];
        $delay = max(0, (int) ($config['delay_hours'] ?? self::DEFAULT_DELAY_HOURS));
        $threshold = $now->copy()->subHours($delay);

        $sent = 0;

        $this->tenant->appointments()
            ->where('status', Appointment::STATUS_COMPLETED)
            ->where('review_requested', false)
            ->where('scheduled_at', '<=', $threshold)
            ->with('contact')
            ->get()
            ->each(function (Appointment $appointment) use ($now, &$sent) {
                // Niente opt-in (template MARKETING) o già contattato di recente:
                // consumiamo comunque l'appuntamento per non rivalutarlo all'infinito.
                if (! $appointment->contact?->opted_in || $this->recentlyRequested($appointment->contact, $now)) {
                    $appointment->update(['review_requested' => true]);

                    return;
                }

                try {
                    $this->request($appointment);
                    $sent++;
                } catch (WhatsAppApiException $e) {
                    // Invio fallito (es. template non approvato): non marchiamo
                    // review_requested → ritenta al prossimo giro.
                    Log::warning('Richiesta recensione fallita', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $sent;
    }

    /**
     * Invia il template recensione per un singolo appuntamento e segna
     * `review_requested`.
     */
    public function request(Appointment $appointment): Message
    {
        $config = $this->automation()?->config ?? [];

        $message = WhatsAppService::for($this->tenant)->sendTemplate(
            $appointment->contact,
            $config['template'] ?? self::DEFAULT_TEMPLATE,
            $config['language'] ?? 'it',
            $this->components($appointment, $config),
        );

        $appointment->update(['review_requested' => true]);

        return $message;
    }

    /** L'Automation `review_request` attiva del tenant, se presente. */
    private function automation(): ?Automation
    {
        return $this->tenant->automations()
            ->where('type', Automation::TYPE_REVIEW_REQUEST)
            ->where('active', true)
            ->first();
    }

    /**
     * Il contatto ha già ricevuto una richiesta recensione (consegnata) entro la
     * finestra di rate limiting? (task 3.3.4)
     */
    private function recentlyRequested(Contact $contact, CarbonInterface $now): bool
    {
        $config = $this->automation()?->config ?? [];
        $days = max(0, (int) ($config['rate_limit_days'] ?? self::DEFAULT_RATE_LIMIT_DAYS));

        if ($days === 0) {
            return false;
        }

        $template = $config['template'] ?? self::DEFAULT_TEMPLATE;

        return $this->tenant->messages()
            ->where('contact_id', $contact->id)
            ->where('type', Message::TYPE_TEMPLATE)
            ->where('content->template', $template)
            ->whereNot('status', Message::STATUS_FAILED)
            ->where('created_at', '>=', $now->copy()->subDays($days))
            ->exists();
    }

    /**
     * Parametri del template (task 3.3.1): nome del contatto nel corpo e, se
     * configurato, il segmento dinamico del button URL verso le recensioni Google.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    private function components(Appointment $appointment, array $config): array
    {
        $components = [[
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $appointment->contact->name ?: 'Cliente'],
            ],
        ]];

        // Button URL dinamico: il template definisce la base (es. il place id
        // Google), qui passiamo solo il suffisso configurato per il tenant.
        if (! empty($config['review_url_param'])) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => (string) $config['review_url_param']],
                ],
            ];
        }

        return $components;
    }
}
