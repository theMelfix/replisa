<?php

use App\Livewire\Appointments;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
    $this->actingAs($this->owner);
});

it('reindirizza gli ospiti al login', function () {
    auth()->logout();
    $this->get('/appointments')->assertRedirect(route('login'));
});

it('crea un appuntamento e il contatto associato per il tenant', function () {
    Livewire::test(Appointments::class)
        ->set('phone', '+39 333 444 5566')
        ->set('name', 'Giulia')
        ->set('scheduled_at', '2026-06-25T15:30')
        ->call('create');

    $contact = Contact::where('phone', '393334445566')->first();

    expect($contact)->not->toBeNull()
        ->and($contact->tenant_id)->toBe($this->tenant->id)
        ->and(Appointment::where('contact_id', $contact->id)->where('status', 'scheduled')->count())->toBe(1);
});

it('valida i campi obbligatori', function () {
    Livewire::test(Appointments::class)
        ->set('phone', '')
        ->set('scheduled_at', '')
        ->call('create')
        ->assertHasErrors(['phone' => 'required', 'scheduled_at' => 'required']);
});

it('aggiorna lo stato di un appuntamento', function () {
    $contact = $this->tenant->contacts()->create(['phone' => '391']);
    $appt = $this->tenant->appointments()->create([
        'contact_id' => $contact->id,
        'scheduled_at' => now()->addDay(),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    Livewire::test(Appointments::class)->call('updateStatus', $appt->id, Appointment::STATUS_CONFIRMED);

    expect($appt->fresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('elimina un appuntamento', function () {
    $contact = $this->tenant->contacts()->create(['phone' => '391']);
    $appt = $this->tenant->appointments()->create([
        'contact_id' => $contact->id,
        'scheduled_at' => now()->addDay(),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    Livewire::test(Appointments::class)->call('delete', $appt->id);

    expect(Appointment::find($appt->id))->toBeNull();
});

it('importa appuntamenti da CSV', function () {
    $csv = "393331110001,Anna,2026-06-25 10:00\n393331110002,Marco,25/06/2026 11:30\nintestazione,da,saltare\n";
    $file = UploadedFile::fake()->createWithContent('appuntamenti.csv', $csv);

    Livewire::test(Appointments::class)
        ->set('csv', $file)
        ->call('import');

    expect(Appointment::count())->toBe(2)
        ->and(Contact::where('phone', '393331110001')->exists())->toBeTrue()
        ->and(Contact::where('phone', '393331110002')->exists())->toBeTrue();
});
