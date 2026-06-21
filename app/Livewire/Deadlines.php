<?php

namespace App\Livewire;

use App\Models\Deadline;
use App\Models\DeadlineReminder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Scadenze (E3.2.6): il tenant vede le scadenze nazionali (gestite dall'admin)
 * e le proprie, e su ciascuna può attivare un promemoria (template + giorni di
 * anticipo) verso i contatti opted-in. Le proprie scadenze sono aggiungibili.
 */
#[Layout('layouts.app')]
class Deadlines extends Component
{
    public string $newName = '';

    public string $newDate = '';

    /** @var array<int, string> template per scadenza id */
    public array $reminderTemplate = [];

    /** @var array<int, int> giorni di anticipo per scadenza id */
    public array $reminderDays = [];

    public function mount(): void
    {
        foreach ($this->tenantReminders() as $deadlineId => $reminder) {
            $this->reminderTemplate[$deadlineId] = $reminder->template_name;
            $this->reminderDays[$deadlineId] = $reminder->days_before;
        }
    }

    public function addDeadline(): void
    {
        $tenant = auth()->user()?->tenant;

        if (! $tenant) {
            $this->dispatch('toast', type: 'error', message: 'Nessuna attività associata.');

            return;
        }

        $data = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newDate' => ['required', 'date'],
        ], attributes: ['newName' => 'nome scadenza', 'newDate' => 'data']);

        Deadline::create([
            'tenant_id' => $tenant->id,
            'name' => $data['newName'],
            'due_date' => $data['newDate'],
            'active' => true,
        ]);

        $this->reset('newName', 'newDate');
        $this->dispatch('toast', type: 'success', message: 'Scadenza aggiunta.');
    }

    public function saveReminder(int $deadlineId): void
    {
        $tenant = auth()->user()?->tenant;

        if (! $tenant || ! Deadline::visibleTo($tenant->id, $tenant->sector)->whereKey($deadlineId)->exists()) {
            $this->dispatch('toast', type: 'error', message: 'Scadenza non valida.');

            return;
        }

        $template = trim($this->reminderTemplate[$deadlineId] ?? '');

        if ($template === '') {
            $this->dispatch('toast', type: 'error', message: 'Indica il template del promemoria.');

            return;
        }

        $days = max(1, min(365, (int) ($this->reminderDays[$deadlineId] ?? 7)));

        DeadlineReminder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'deadline_id' => $deadlineId],
            ['template_name' => $template, 'days_before' => $days, 'active' => true],
        );

        $this->dispatch('toast', type: 'success', message: 'Promemoria attivato.');
    }

    public function toggleReminder(int $deadlineId): void
    {
        $reminder = DeadlineReminder::where('deadline_id', $deadlineId)->first();

        $reminder?->update(['active' => ! $reminder->active]);
    }

    public function deleteDeadline(int $deadlineId): void
    {
        $tenant = auth()->user()?->tenant;

        // Solo scadenze proprie del tenant (mai le nazionali).
        Deadline::where('tenant_id', $tenant?->id)->whereKey($deadlineId)->first()?->delete();

        $this->dispatch('toast', type: 'info', message: 'Scadenza eliminata.');
    }

    /** @return Collection<int, DeadlineReminder> indicizzata per deadline_id */
    protected function tenantReminders(): Collection
    {
        return DeadlineReminder::get()->keyBy('deadline_id');
    }

    public function render(): View
    {
        $tenant = auth()->user()?->tenant;

        $deadlines = Deadline::visibleTo($tenant?->id, $tenant?->sector)
            ->where('active', true)
            ->whereDate('due_date', '>=', today())
            ->orderBy('due_date')
            ->get();

        return view('livewire.deadlines', [
            'deadlines' => $deadlines,
            'reminders' => $this->tenantReminders(),
        ]);
    }
}
