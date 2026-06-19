<?php

namespace App\Livewire;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sezione Contatti (E4.2.2): lista e ricerca dei contatti del tenant.
 * Scoped per-tenant dal TenantScope.
 */
#[Layout('layouts.app')]
class Contacts extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $contacts = Contact::query()
            ->withCount('messages')
            ->when($this->search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('phone', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('last_seen_at')
            ->paginate(20);

        return view('livewire.contacts', ['contacts' => $contacts]);
    }
}
