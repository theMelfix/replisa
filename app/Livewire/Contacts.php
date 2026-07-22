<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Message;
use App\Models\Tag;
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

    /** Filtro lista per etichetta (id tag, o vuoto = tutte). */
    #[Url]
    public string $tagFilter = '';

    /** Contatto di cui è aperta la conversazione (null = pannello chiuso). */
    public ?int $selectedId = null;

    /** Nome della nuova etichetta da assegnare al contatto selezionato. */
    public string $newTag = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTagFilter(): void
    {
        $this->resetPage();
    }

    /** Assegna un'etichetta (nuova o esistente) al contatto aperto. */
    public function addTag(): void
    {
        $name = trim($this->newTag);

        if ($name === '' || ! $this->selectedContact) {
            return;
        }

        // firstOrCreate scoped al tenant (auto-fill tenant_id da BelongsToTenant).
        $tag = Tag::firstOrCreate(['name' => $name]);
        $this->selectedContact->tags()->syncWithoutDetaching([$tag->id]);

        $this->newTag = '';
    }

    public function removeTag(int $tagId): void
    {
        $this->selectedContact?->tags()->detach($tagId);
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
            ->with('tags')
            ->when($this->search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('phone', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%")
            ))
            ->when($this->tagFilter !== '', fn ($q) => $q->whereHas('tags', fn ($t) => $t->whereKey((int) $this->tagFilter)))
            ->orderByDesc('last_seen_at')
            ->paginate(20);

        return view('livewire.contacts', [
            'contacts' => $contacts,
            'selectedContact' => $this->selectedContact?->load('tags'),
            'conversation' => $this->conversation,
            'allTags' => Tag::orderBy('name')->get(),
        ]);
    }
}
