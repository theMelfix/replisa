<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sezione Contatti (E4.2.2): lista e ricerca dei contatti del tenant, con
 * dettaglio conversazione in un pannello laterale. Scoped per-tenant dal
 * TenantScope (che copre sia i contatti sia i loro messaggi).
 */
#[Layout('layouts.app')]
class Contacts extends Component
{
    use WithPagination;

    /** Numero massimo di messaggi mostrati nel dettaglio conversazione. */
    public const CONVERSATION_LIMIT = 200;

    #[Url]
    public string $search = '';

    /** Contatto di cui è aperta la conversazione (null = pannello chiuso). */
    public ?int $selectedId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function showConversation(int $id): void
    {
        // Il TenantScope garantisce che si possa aprire solo un contatto del
        // proprio tenant: un id altrui semplicemente non viene trovato.
        $this->selectedId = Contact::whereKey($id)->exists() ? $id : null;
    }

    public function closeConversation(): void
    {
        $this->selectedId = null;
    }

    /** Il contatto selezionato, se il pannello è aperto. */
    public function getSelectedContactProperty(): ?Contact
    {
        return $this->selectedId ? Contact::find($this->selectedId) : null;
    }

    /**
     * I messaggi del contatto selezionato, dal più vecchio al più recente
     * (ordine cronologico da chat). Limitati agli ultimi CONVERSATION_LIMIT.
     *
     * @return Collection<int, Message>
     */
    public function getConversationProperty(): Collection
    {
        if (! $this->selectedContact) {
            return collect();
        }

        return $this->selectedContact->messages()
            ->latest()
            ->limit(self::CONVERSATION_LIMIT)
            ->get()
            ->reverse()
            ->values();
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

        return view('livewire.contacts', [
            'contacts' => $contacts,
            'selectedContact' => $this->selectedContact,
            'conversation' => $this->conversation,
        ]);
    }
}
