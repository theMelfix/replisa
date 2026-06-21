<?php

namespace App\Livewire\Admin;

use App\Models\Deadline;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Gestione del calendario scadenze nazionali (E3.2.6) da parte del super-admin:
 * aggiunta, aggiornamento data (a ogni annuncio ufficiale), attiva/disattiva,
 * eliminazione. Le scadenze nazionali hanno `tenant_id` null.
 */
#[Layout('layouts.app')]
class Deadlines extends Component
{
    public string $name = '';

    public string $due_date = '';

    /** @var array<int, string> data (YYYY-MM-DD) per scadenza id, per la modifica inline */
    public array $dates = [];

    public function mount(): void
    {
        foreach (Deadline::national()->get() as $deadline) {
            $this->dates[$deadline->id] = $deadline->due_date->format('Y-m-d');
        }
    }

    public function add(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
        ], attributes: ['due_date' => 'data']);

        Deadline::create([
            'tenant_id' => null,
            'name' => $data['name'],
            'due_date' => $data['due_date'],
            'active' => true,
        ]);

        $this->reset('name', 'due_date');
        $this->dispatch('toast', type: 'success', message: 'Scadenza nazionale aggiunta.');
    }

    public function updateDate(int $id): void
    {
        $deadline = Deadline::national()->find($id);
        $date = $this->dates[$id] ?? null;

        if ($deadline && $date) {
            $deadline->update(['due_date' => $date]);
            $this->dispatch('toast', type: 'success', message: 'Data aggiornata.');
        }
    }

    public function toggleActive(int $id): void
    {
        $deadline = Deadline::national()->find($id);
        $deadline?->update(['active' => ! $deadline->active]);
    }

    public function delete(int $id): void
    {
        Deadline::national()->whereKey($id)->first()?->delete();
        $this->dispatch('toast', type: 'info', message: 'Scadenza eliminata.');
    }

    public function render(): View
    {
        return view('livewire.admin.deadlines', [
            'deadlines' => Deadline::national()->orderBy('due_date')->get(),
        ]);
    }
}
