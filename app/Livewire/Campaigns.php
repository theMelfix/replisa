<?php

namespace App\Livewire;

use App\Jobs\SendCampaign;
use App\Models\Campaign;
use App\Models\Tag;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
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

    /** Segmento: id etichetta, o vuoto = tutti i contatti opted-in. */
    public string $tag_id = '';

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
            // Il tag deve appartenere al tenant (Rule::exists scoped al tenant).
            'tag_id' => ['nullable', 'integer', Rule::exists('tags', 'id')->where('tenant_id', $tenant->id)],
        ], attributes: [
            'name' => 'nome campagna',
            'template_name' => 'template',
            'tag_id' => 'segmento',
        ]);

        $tagId = $this->tag_id !== '' ? (int) $this->tag_id : null;
        $recipients = $tenant->contacts()->campaignRecipients($tagId)->count();

        if ($recipients === 0) {
            $this->dispatch('toast', type: 'error', message: 'Nessun contatto con opt-in nel segmento scelto.');

            return;
        }

        // Pacchetto messaggi campagna inclusi nel mese (null = illimitati).
        $messagesLimit = PlanLimits::for($tenant)->campaignMessagesLimit();

        if ($messagesLimit !== null) {
            $usedThisMonth = Campaign::where('created_at', '>=', now()->startOfMonth())->sum('total');

            if ($usedThisMonth + $recipients > $messagesLimit) {
                $this->dispatch('toast', type: 'error', message: 'Hai esaurito i messaggi campagna inclusi nel tuo piano questo mese.');

                return;
            }
        }

        $campaign = Campaign::create([
            'tag_id' => $tagId,
            'name' => $data['name'],
            'template_name' => $data['template_name'],
            'language' => $data['language'],
            'include_name' => $this->include_name,
            'status' => Campaign::STATUS_PENDING,
            'total' => $recipients,
        ]);

        SendCampaign::dispatch($campaign);

        $this->reset('name', 'template_name', 'include_name', 'tag_id');
        $this->dispatch('toast', type: 'success', message: "Campagna avviata: invio a {$recipients} contatti.");
    }

    public function render(): View
    {
        $tenant = auth()->user()?->tenant;
        $tagId = $this->tag_id !== '' ? (int) $this->tag_id : null;

        return view('livewire.campaigns', [
            'campaigns' => Campaign::with('tag')->orderByDesc('created_at')->paginate(10),
            // Destinatari del segmento attualmente selezionato (aggiornato live).
            'optedInCount' => $tenant ? $tenant->contacts()->campaignRecipients($tagId)->count() : 0,
            'tags' => $tenant ? Tag::withCount(['contacts' => fn ($q) => $q->where('opted_in', true)])->orderBy('name')->get() : collect(),
            'allowed' => $tenant ? PlanLimits::for($tenant)->allows('campaigns') : false,
        ]);
    }
}
