<?php

namespace App\Livewire;

use App\Models\Message;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Log messaggi (E4.2.5): cronologia dei messaggi del tenant con ricerca per
 * contatto e filtri direzione/stato. Scoped per-tenant dal TenantScope.
 */
#[Layout('layouts.app')]
class Messages extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $direction = '';

    #[Url]
    public string $status = '';

    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'direction', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $messages = Message::query()
            ->with('contact')
            ->when($this->search !== '', fn ($q) => $q->whereHas('contact', fn ($c) => $c->where(
                fn ($w) => $w->where('phone', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%")
            )))
            ->when($this->direction !== '', fn ($q) => $q->where('direction', $this->direction))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.messages', ['messages' => $messages]);
    }
}
