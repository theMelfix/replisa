<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Sezione Appuntamenti (E4.2.4): CRUD manuale + import CSV. Tutto scoped
 * per-tenant (TenantScope + auto-fill tenant_id sui modelli BelongsToTenant).
 */
#[Layout('layouts.app')]
class Appointments extends Component
{
    use WithFileUploads, WithPagination;

    // Form nuovo appuntamento
    public string $phone = '';

    public string $name = '';

    public string $scheduled_at = '';

    // Import CSV (colonne: telefono, nome, data ora)
    public $csv;

    public ?string $importMessage = null;

    public function create(): void
    {
        $data = $this->validate([
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $contact = Contact::firstOrCreate(
            ['phone' => preg_replace('/\D+/', '', $data['phone'])],
            ['name' => $data['name'] ?: null],
        );

        Appointment::create([
            'contact_id' => $contact->id,
            'scheduled_at' => Carbon::parse($data['scheduled_at']),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $this->reset('phone', 'name', 'scheduled_at');
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! in_array($status, [
            Appointment::STATUS_SCHEDULED,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_COMPLETED,
        ], true)) {
            return;
        }

        Appointment::whereKey($id)->first()?->update(['status' => $status]);
    }

    public function delete(int $id): void
    {
        Appointment::whereKey($id)->first()?->delete();
        $this->resetPage();
    }

    public function import(): void
    {
        $this->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $rows = array_map('str_getcsv', file($this->csv->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $imported = 0;

        foreach ($rows as $row) {
            $phone = preg_replace('/\D+/', '', $row[0] ?? '');
            if ($phone === '' || ! is_numeric($phone)) {
                continue; // salta header o righe non valide
            }

            $when = $this->parseDate($row[2] ?? '');
            if (! $when) {
                continue;
            }

            $contact = Contact::firstOrCreate(['phone' => $phone], ['name' => trim($row[1] ?? '') ?: null]);
            Appointment::create([
                'contact_id' => $contact->id,
                'scheduled_at' => $when,
                'status' => Appointment::STATUS_SCHEDULED,
            ]);
            $imported++;
        }

        $this->reset('csv');
        $this->importMessage = "Importati {$imported} appuntamenti.";
        $this->resetPage();
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Carbon 3: createFromFormat lancia un'eccezione se il formato non combacia.
        foreach (['Y-m-d H:i', 'Y-m-d H:i:s', 'd/m/Y H:i', 'd/m/Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        $appointments = Appointment::with('contact')
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        return view('livewire.appointments', ['appointments' => $appointments]);
    }
}
