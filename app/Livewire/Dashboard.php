<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dashboard overview (E4.2.1): numeri chiave del tenant. Tutte le query sono
 * filtrate automaticamente per tenant dal {@see TenantScope}.
 */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.dashboard', [
            'inviati' => Message::where('direction', Message::DIRECTION_OUTBOUND)->count(),
            'ricevuti' => Message::where('direction', Message::DIRECTION_INBOUND)->count(),
            'contatti' => Contact::count(),
            'conversazioniAttive' => Conversation::where('expires_at', '>', now())->count(),
            'appuntamentiProssimi' => Appointment::where('status', Appointment::STATUS_SCHEDULED)
                ->where('scheduled_at', '>', now())
                ->count(),
        ]);
    }
}
