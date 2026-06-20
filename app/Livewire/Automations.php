<?php

namespace App\Livewire;

use App\Exceptions\TenantContextException;
use App\Models\Automation;
use App\Models\Scopes\TenantScope;
use App\Support\PlanLimits;
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

        $tenant = auth()->user()?->tenant;

        try {
            $automation = Automation::where('type', $type)->first();

            $activating = $automation ? ! $automation->active : true;

            // Enforcement limiti di piano (E4.2.6): blocca l'attivazione di una
            // nuova automazione oltre il numero consentito dal piano.
            if ($activating && $tenant && ! PlanLimits::for($tenant)->canActivateAnotherAutomation()) {
                $limit = PlanLimits::for($tenant)->automationsLimit();
                $this->dispatch('toast', type: 'error', message: "Il tuo piano consente fino a {$limit} automazioni attive. Passa a un piano superiore per attivarne altre.");

                return;
            }

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
        } catch (TenantContextException $e) {
            // Errore di dominio atteso (es. super-admin senza tenant): mostra un
            // toast col messaggio invece di propagare un 500. Vedi <x-toast-hub />.
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
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
