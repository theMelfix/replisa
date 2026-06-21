<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Email d'invito inviata quando l'admin censisce un nuovo cliente (E5): contiene
 * un URL firmato (valido 7 giorni) verso la pagina di attivazione, dove il
 * cliente imposta la propria password. Niente password in chiaro condivise.
 */
class TenantInvitation extends Notification
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'invitation.accept',
            now()->addDays(7),
            ['user' => $notifiable->getKey()],
        );

        return (new MailMessage)
            ->subject('Benvenuto in Replisa — attiva il tuo account')
            ->greeting('Ciao '.$notifiable->name.',')
            ->line('Il tuo account Replisa è pronto. Imposta la password per accedere alla dashboard.')
            ->action('Attiva account', $url)
            ->line('Il link è valido per 7 giorni. Se non hai richiesto questo account, ignora questa email.');
    }
}
