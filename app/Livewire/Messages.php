<?php

namespace App\Livewire;

use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Log messaggi (E4.2.5): cronologia dei messaggi del tenant con ricerca per
 * contatto e filtri direzione/stato/tipo/data. Scoped per-tenant dal TenantScope.
 */
#[Layout('layouts.app')]
class Messages extends Component
{
    use WithPagination;

    /** Filtri sincronizzati nell'URL (condivisibili/segnalibili). */
    private const FILTERS = ['search', 'direction', 'status', 'type', 'from', 'to'];

    #[Url]
    public string $search = '';

    #[Url]
    public string $direction = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    /** Estremi (inclusi) dell'intervallo date, formato Y-m-d. */
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function updated(string $name): void
    {
        if (in_array($name, self::FILTERS, true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
    }

    /** Parsa una data `Y-m-d` dall'input, o null se vuota/non valida. */
    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Exception) {
            return null;
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
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            // Estremi inclusivi: `to` copre l'intera giornata fino a fine giorno.
            // Le date arrivano dall'URL → parsing difensivo (ignora valori invalidi).
            ->when($this->parseDate($this->from), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when($this->parseDate($this->to), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()))
            ->latest()
            ->paginate(20);

        return view('livewire.messages', [
            'messages' => $messages,
            'types' => [
                Message::TYPE_TEXT => 'Testo',
                Message::TYPE_TEMPLATE => 'Template',
                Message::TYPE_INTERACTIVE => 'Interattivo',
            ],
        ]);
    }
}
