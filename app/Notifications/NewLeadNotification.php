<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avvisa il team (E5.1.2) quando arriva una richiesta di contatto/demo dal form
 * pubblico della landing. Inviata on-demand all'indirizzo di
 * `services.contact.notify_email`.
 */
class NewLeadNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Lead $lead) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Nuova richiesta demo da '.$this->lead->name)
            ->greeting('Nuovo lead da Replisa')
            ->line('**Nome:** '.$this->lead->name)
            ->line('**Email:** '.$this->lead->email)
            ->line('**Telefono:** '.($this->lead->phone ?: '—'))
            ->line('**Attività:** '.($this->lead->business ?: '—'));

        if (filled($this->lead->message)) {
            $mail->line('**Messaggio:**')->line($this->lead->message);
        }

        // Rispondi direttamente al lead cliccando "Rispondi" nel client email.
        return $mail->replyTo($this->lead->email, $this->lead->name);
    }
}
