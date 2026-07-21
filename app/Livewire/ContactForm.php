<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

/**
 * Form di contatto/demo della landing (E5.1.2): sostituisce il vecchio CTA
 * `mailto:`. Salva un {@see Lead} e notifica il team via email. Pubblico
 * (nessun auth): protetto da un honeypot anti-bot.
 */
class ContactForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $business = '';

    public string $message = '';

    /** Honeypot: un campo nascosto che solo i bot compilano. */
    public string $website = '';

    public bool $sent = false;

    public function submit(): void
    {
        // Bot: fingi il successo senza salvare nulla, per non dare segnali.
        if (filled($this->website)) {
            $this->sent = true;

            return;
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'business' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'name' => 'nome',
            'email' => 'email',
            'message' => 'messaggio',
        ]);

        $lead = Lead::create([
            ...$data,
            'status' => Lead::STATUS_NEW,
            'ip' => request()->ip(),
        ]);

        // On-demand: il destinatario è un indirizzo di config, non un utente su DB.
        Notification::route('mail', config('services.contact.notify_email'))
            ->notify(new NewLeadNotification($lead));

        $this->reset('name', 'email', 'phone', 'business', 'message');
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.contact-form');
    }
}
