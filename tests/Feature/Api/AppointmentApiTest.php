<?php

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'Studio A', 'phone_number_id' => 'PNID-1', 'access_token' => 'T']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner', 'email' => 'owner@example.com', 'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('crea un appuntamento e il contatto se non esiste', function () {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/appointments', [
        'phone' => '39 333 444 5566',
        'name' => 'Mario Rossi',
        'scheduled_at' => now()->addDays(2)->toIso8601String(),
    ])
        ->assertCreated()
        ->assertJsonPath('status', 'scheduled')
        ->assertJsonPath('contact.phone', '393334445566');

    expect(Appointment::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count())->toBe(1)
        ->and(Contact::withoutGlobalScopes()->where('phone', '393334445566')->exists())->toBeTrue();
});

it('riusa un contatto esistente', function () {
    $contact = Contact::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'phone' => '393334445566', 'name' => 'Mario',
    ]);

    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/appointments', [
        'phone' => '393334445566',
        'scheduled_at' => now()->addDay()->toIso8601String(),
    ])->assertCreated()->assertJsonPath('contact.id', $contact->id);

    expect(Contact::withoutGlobalScopes()->count())->toBe(1);
});

it('rifiuta una data nel passato', function () {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/appointments', [
        'phone' => '393334445566',
        'scheduled_at' => now()->subDay()->toIso8601String(),
    ])->assertStatus(422)->assertJsonValidationErrors('scheduled_at');
});

it('rispetta il limite contatti del piano', function () {
    config(['plans.plans.starter.limits.contacts' => 0]);
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/appointments', [
        'phone' => '393339990000',
        'scheduled_at' => now()->addDay()->toIso8601String(),
    ])->assertStatus(422);

    expect(Appointment::withoutGlobalScopes()->count())->toBe(0);
});

it('richiede autenticazione', function () {
    $this->postJson('/api/v1/appointments', [])->assertUnauthorized();
});
