<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\Automation\WelcomeFlow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Processa async un payload webhook Meta (task 2.2.4/2.2.5/2.2.6).
 *
 * Gestisce sia i messaggi in ingresso (`messages`) sia gli aggiornamenti di
 * stato dei messaggi inviati (`statuses`). Idempotente: i webhook possono
 * essere riconsegnati, quindi usiamo il `meta_message_id` come chiave.
 */
class ProcessWhatsAppWebhook implements ShouldQueue
{
    use Queueable;

    /** @param array<string, mixed> $payload */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        foreach (data_get($this->payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                if (data_get($change, 'field') !== 'messages') {
                    continue;
                }

                $value = data_get($change, 'value', []);
                $phoneNumberId = data_get($value, 'metadata.phone_number_id');
                $tenant = Tenant::where('phone_number_id', $phoneNumberId)->first();

                if (! $tenant) {
                    Log::warning('Webhook per phone_number_id sconosciuto', ['phone_number_id' => $phoneNumberId]);

                    continue;
                }

                $this->handleInboundMessages($tenant, $value);
                $this->handleStatusUpdates($tenant, $value);
            }
        }
    }

    /** @param array<string, mixed> $value */
    private function handleInboundMessages(Tenant $tenant, array $value): void
    {
        // Mappa wa_id => nome profilo, per arricchire i contatti.
        $names = collect(data_get($value, 'contacts', []))
            ->mapWithKeys(fn (array $c) => [$c['wa_id'] => data_get($c, 'profile.name')]);

        foreach (data_get($value, 'messages', []) as $message) {
            $from = data_get($message, 'from');
            $wamid = data_get($message, 'id');

            $contact = $tenant->contacts()->firstOrNew(['phone' => $from]);
            $contact->name ??= $names->get($from);
            $contact->last_seen_at = now();
            $contact->save();

            $isFirstContact = $contact->wasRecentlyCreated;

            $logged = Message::firstOrCreate(
                ['tenant_id' => $tenant->id, 'meta_message_id' => $wamid],
                [
                    'contact_id' => $contact->id,
                    'direction' => Message::DIRECTION_INBOUND,
                    'type' => data_get($message, 'type', 'unknown'),
                    'content' => $this->extractContent($message),
                    'status' => Message::STATUS_RECEIVED,
                ],
            );

            // Solo su messaggi appena loggati: evita di rieseguire le automazioni
            // se il webhook viene riconsegnato (idempotenza).
            if ($logged->wasRecentlyCreated) {
                $this->runAutomations($tenant, $contact, $message, $isFirstContact);
            }
        }
    }

    /**
     * Inoltra il messaggio in ingresso ai flussi di automazione (E3.1 Welcome Flow):
     * trigger di benvenuto al primo contatto, routing delle risposte ai bottoni.
     *
     * @param  array<string, mixed>  $message
     */
    private function runAutomations(Tenant $tenant, Contact $contact, array $message, bool $isFirstContact): void
    {
        $welcome = WelcomeFlow::for($tenant);

        if ($isFirstContact) {
            $welcome->greet($contact);

            return;
        }

        if (data_get($message, 'interactive.type') === 'button_reply') {
            $welcome->handleButtonReply($contact, (string) data_get($message, 'interactive.button_reply.id'));
        }
    }

    /** @param array<string, mixed> $value */
    private function handleStatusUpdates(Tenant $tenant, array $value): void
    {
        foreach (data_get($value, 'statuses', []) as $status) {
            $message = $tenant->messages()
                ->where('meta_message_id', data_get($status, 'id'))
                ->first();

            // Status di un messaggio che non abbiamo loggato: ignora.
            if (! $message) {
                continue;
            }

            $message->update(array_filter([
                'status' => data_get($status, 'status'),
                'error' => data_get($status, 'errors'),
            ], fn ($v) => $v !== null));
        }
    }

    /**
     * Normalizza il contenuto per tipo (task 2.2.4).
     *
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function extractContent(array $message): array
    {
        return match (data_get($message, 'type')) {
            'text' => [
                'body' => data_get($message, 'text.body'),
            ],
            'interactive' => [
                'reply_type' => data_get($message, 'interactive.type'), // button_reply | list_reply
                'id' => data_get($message, 'interactive.'.data_get($message, 'interactive.type').'.id'),
                'title' => data_get($message, 'interactive.'.data_get($message, 'interactive.type').'.title'),
            ],
            'button' => [ // quick-reply di un template
                'payload' => data_get($message, 'button.payload'),
                'text' => data_get($message, 'button.text'),
            ],
            default => ['raw' => $message],
        };
    }
}
