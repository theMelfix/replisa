<?php

namespace App\Livewire;

use App\Models\Automation;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Gestione automazioni del tenant (E4.2.3): attiva/disattiva i 3 flussi MVP.
 * Le query sono scoped per-tenant dal {@see TenantScope};
 * in creazione il `tenant_id` è compilato dal trait BelongsToTenant.
 */
#[Layout('layouts.app')]
class Automations extends Component
{
    /** @var array<string, array{label: string, desc: string, trigger: string}> */
    public const FLOWS = [
        Automation::TYPE_WELCOME => [
            'label' => 'Benvenuto automatico',
            'desc' => 'Menu interattivo al primo messaggio di un nuovo contatto.',
            'trigger' => 'inbound',
        ],
        Automation::TYPE_APPOINTMENT_REMINDER => [
            'label' => 'Promemoria appuntamenti',
            'desc' => 'Reminder a -24h e -2h con conferma/disdetta.',
            'trigger' => 'schedule',
        ],
        Automation::TYPE_REVIEW_REQUEST => [
            'label' => 'Richiesta recensione',
            'desc' => 'Richiesta recensione dopo un appuntamento completato.',
            'trigger' => 'schedule',
        ],
    ];

    public function toggle(string $type): void
    {
        if (! array_key_exists($type, self::FLOWS)) {
            return;
        }

        $automation = Automation::where('type', $type)->first();

        if ($automation) {
            $automation->update(['active' => ! $automation->active]);

            return;
        }

        Automation::create([
            'type' => $type,
            'trigger' => self::FLOWS[$type]['trigger'],
            'config' => [],
            'active' => true,
        ]);
    }

    public function render(): View
    {
        $active = Automation::pluck('active', 'type');

        return view('livewire.automations', [
            'flows' => self::FLOWS,
            'active' => $active,
        ]);
    }
}
