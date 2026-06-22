<?php

namespace App\Livewire;

use App\Jobs\SendCampaign;
use App\Models\Campaign;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Campagne e comunicazioni (E3.4): compositore di una campagna (template
 * MARKETING) verso i contatti opted-in del tenant; l'invio è delegato al job
 * {@see SendCampaign} in coda. Scoped per-tenant (TenantScope).
 */
#[Layout('layouts.app')]
class Campaigns extends Component
{
    use WithPagination;

    public string $name = '';

    public string $template_name = '';

    public string $language = 'it';

    public bool $include_name = false;

    public function send(): void
    {
        $tenant = auth()->user()?->tenant;

        if (! $tenant) {
            $this->dispatch('toast', type: 'error', message: 'Nessuna attività associata al tuo account.');

            return;
        }

        if (! PlanLimits::for($tenant)->allows('campaigns')) {
            $this->dispatch('toast', type: 'error', message: 'Le campagne sono incluse dal piano Base in su. Aggiorna il piano per inviarle.');

            return;
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_name' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:5'],
        ], attributes: [
            'name' => 'nome campagna',
            'template_name' => 'template',
        ]);

        $recipients = $tenant->contacts()->where('opted_in', true)->count();

        if ($recipients === 0) {
            $this->dispatch('toast', type: 'error', message: 'Nessun contatto con opt-in a cui inviare.');

            return;
        }

        $campaign = Campaign::create([
            'name' => $data['name'],
            'template_name' => $data['template_name'],
            'language' => $data['language'],
            'include_name' => $this->include_name,
            'status' => Campaign::STATUS_PENDING,
            'total' => $recipients,
        ]);

        SendCampaign::dispatch($campaign);

        $this->reset('name', 'template_name', 'include_name');
        $this->dispatch('toast', type: 'success', message: "Campagna avviata: invio a {$recipients} contatti.");
    }

    public function render(): View
    {
        $tenant = auth()->user()?->tenant;

        return view('livewire.campaigns', [
            'campaigns' => Campaign::orderByDesc('created_at')->paginate(10),
            'optedInCount' => $tenant ? $tenant->contacts()->where('opted_in', true)->count() : 0,
            'allowed' => $tenant ? PlanLimits::for($tenant)->allows('campaigns') : false,
        ]);
    }
}
