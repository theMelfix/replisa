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
 * Reminder Appuntamento (E3.2): invia un promemoria template a -24h e -2h
 * dall'appuntamento e instrada le risposte ai bottoni Conferma/Disdici.
 *
 * Scoped per-tenant come {@see WhatsAppService}; si attiva solo se il tenant ha
 * un'Automation `appointment_reminder` attiva. Il reminder parte fuori dalla
 * finestra di servizio 24h, quindi viaggia come **template approvato** (task
 * 3.2.1): nome lingua e parametri sono configurabili via `automations.config`.
 */
class AppointmentReminder
{
    /** Offset di default (ore prima dell'appuntamento) a cui inviare un reminder. */
    public const DEFAULT_OFFSETS = [24, 2];

    public const DEFAULT_TEMPLATE = 'appointment_reminder';

    public const DEFAULT_CONFIRM_PAYLOAD = 'CONFIRM';

    public const DEFAULT_CANCEL_PAYLOAD = 'CANCEL';

    public function __construct(private readonly Tenant $tenant) {}

    public static function for(Tenant $tenant): self
    {
        return new self($tenant);
    }

    /**
     * Trova gli appuntamenti che hanno un reminder dovuto e li invia (task 3.2.2).
     * Idempotente: un appuntamento riceve al massimo un reminder per ciascuna
     * finestra, anche se il job gira più volte nello stesso intervallo.
     *
     * @return int numero di reminder effettivamente inviati
     */
    public function dispatchDue(?CarbonInterface $now = null): int
    {
        $automation = $this->automation();

        if (! $automation) {
            return 0;
        }

        $now = $now ? $now->copy() : now();
        $offsets = $this->offsets($automation);
        $horizon = $now->copy()->addHours(max($offsets));

        $sent = 0;

        $this->tenant->appointments()
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->whereBetween('scheduled_at', [$now, $horizon])
            ->with('contact')
            ->get()
            ->each(function (Appointment $appointment) use ($now, $offsets, &$sent) {
                $offset = $this->dueOffset($appointment, $now, $offsets);

                if ($offset === null) {
                    return;
                }

                try {
                    $this->remind($appointment, $now);
                    $sent++;
                } catch (WhatsAppApiException $e) {
                    // Reminder fallito (es. template non approvato, numero invalido):
                    // non marchiamo reminded_at → ritenta al prossimo giro.
                    Log::warning('Reminder appuntamento fallito', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        return $sent;
    }

    /**
     * Invia il template reminder per un singolo appuntamento e segna `reminded_at`.
     */
    public function remind(Appointment $appointment, ?CarbonInterface $now = null): Message
    {
        $automation = $this->automation();
        $config = $automation?->config ?? [];

        $message = WhatsAppService::for($this->tenant)->sendTemplate(
            $appointment->contact,
            $config['template'] ?? self::DEFAULT_TEMPLATE,
            $config['language'] ?? 'it',
            $this->components($appointment),
        );

        $appointment->forceFill(['reminded_at' => $now ?? now()])->save();

        return $message;
    }

    /**
     * Routing della risposta a un bottone del reminder (task 3.2.4): aggiorna lo
     * stato dell'appuntamento più imminente del contatto.
     *
     * @param  string|null  $payload  il `button.payload` del quick-reply del template
     */
    public function handleButtonReply(Contact $contact, ?string $payload): ?Appointment
    {
        $automation = $this->automation();

        if (! $automation || $payload === null) {
            return null;
        }

        $config = $automation->config ?? [];

        $status = match ($payload) {
            $config['confirm_payload'] ?? self::DEFAULT_CONFIRM_PAYLOAD => Appointment::STATUS_CONFIRMED,
            $config['cancel_payload'] ?? self::DEFAULT_CANCEL_PAYLOAD => Appointment::STATUS_CANCELLED,
            default => null,
        };

        if ($status === null) {
            return null;
        }

        // Il template quick-reply non porta l'id appuntamento: associamo la risposta
        // al prossimo appuntamento ancora `scheduled` del contatto (caso tipico: uno solo).
        $appointment = $contact->appointments()
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->first();

        if (! $appointment) {
            return null;
        }

        $appointment->update(['status' => $status]);

        return $appointment;
    }

    /** L'Automation `appointment_reminder` attiva del tenant, se presente. */
    private function automation(): ?Automation
    {
        return $this->tenant->automations()
            ->where('type', Automation::TYPE_APPOINTMENT_REMINDER)
            ->where('active', true)
            ->first();
    }

    /**
     * Offset (in ore) a cui l'appuntamento è "dovuto" un reminder non ancora
     * inviato, oppure null se nessuna finestra è scattata o è già stata coperta.
     *
     * @param  array<int, int>  $offsets
     */
    private function dueOffset(Appointment $appointment, CarbonInterface $now, array $offsets): ?int
    {
        sort($offsets); // ascendente: la finestra più urgente per prima

        $hoursUntil = abs($now->diffInMinutes($appointment->scheduled_at)) / 60;
        $due = $this->offsetFor($hoursUntil, $offsets);

        if ($due === null) {
            return null;
        }

        if ($appointment->reminded_at) {
            $hoursAtLast = abs($appointment->reminded_at->diffInMinutes($appointment->scheduled_at)) / 60;
            $lastOffset = $this->offsetFor($hoursAtLast, $offsets);

            // Già rimandato a questa finestra o a una più urgente: niente da fare.
            if ($lastOffset !== null && $lastOffset <= $due) {
                return null;
            }
        }

        return $due;
    }

    /**
     * La più piccola finestra (offset) che copre un appuntamento a `$hoursUntil` ore.
     *
     * @param  array<int, int>  $offsets  ordinati in modo ascendente
     */
    private function offsetFor(float $hoursUntil, array $offsets): ?int
    {
        foreach ($offsets as $offset) {
            if ($hoursUntil <= $offset) {
                return $offset;
            }
        }

        return null;
    }

    /** @return array<int, int> */
    private function offsets(Automation $automation): array
    {
        $offsets = $automation->config['offsets'] ?? self::DEFAULT_OFFSETS;

        return array_values(array_filter(array_map('intval', $offsets), fn (int $o) => $o > 0))
            ?: self::DEFAULT_OFFSETS;
    }

    /**
     * Parametri del corpo del template: nome contatto, data, ora (task 3.2.1).
     *
     * @return array<int, array<string, mixed>>
     */
    private function components(Appointment $appointment): array
    {
        return [[
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $appointment->contact->name ?: 'Cliente'],
                ['type' => 'text', 'text' => $appointment->scheduled_at->format('d/m/Y')],
                ['type' => 'text', 'text' => $appointment->scheduled_at->format('H:i')],
            ],
        ]];
    }
}
