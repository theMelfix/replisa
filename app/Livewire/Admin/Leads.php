<?php

namespace App\Livewire\Admin;

use App\Models\Lead;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestione dei lead dalla landing (E5.1.2) da parte del super-admin: lista
 * paginata con ricerca e filtro per stato, avanzamento stato (nuovo →
 * contattato → convertito / archiviato) ed eliminazione. I lead non sono
 * tenant-owned, quindi qui non c'è TenantScope: la protezione è la rotta
 * `role:super-admin`.
 */
#[Layout('layouts.app')]
class Leads extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function setStatus(int $id, string $status): void
    {
        if (! in_array($status, self::statuses(), true)) {
            return;
        }

        Lead::whereKey($id)->update(['status' => $status]);
        $this->dispatch('toast', type: 'success', message: 'Stato aggiornato.');
    }

    public function delete(int $id): void
    {
        Lead::whereKey($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Lead eliminato.');
    }

    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [
            Lead::STATUS_NEW,
            Lead::STATUS_CONTACTED,
            Lead::STATUS_CONVERTED,
            Lead::STATUS_ARCHIVED,
        ];
    }

    public function render(): View
    {
        $leads = Lead::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('business', 'like', "%{$this->search}%")
            ))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.leads', [
            'leads' => $leads,
            'counts' => Lead::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }
}
