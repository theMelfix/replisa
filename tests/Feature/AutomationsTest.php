<?php

use App\Livewire\Automations;
use App\Models\Automation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'Studio A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/automations')->assertRedirect(route('login'));
});

it('mostra i tre flussi a un owner', function () {
    $this->actingAs($this->owner);

    $this->get('/automations')
        ->assertOk()
        ->assertSee('Benvenuto automatico')
        ->assertSee('Promemoria')
        ->assertSee('Campagne e comunicazioni');
});

it('attiva un flusso creando l\'automation per il tenant', function () {
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)->call('toggle', Automation::TYPE_WELCOME);

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_WELCOME)->first();

    expect($automation)->not->toBeNull()
        ->and($automation->active)->toBeTrue()
        ->and($automation->tenant_id)->toBe($this->tenant->id);
});

it('disattiva un flusso già attivo al secondo toggle', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(Automations::class);
    $component->call('toggle', Automation::TYPE_APPOINTMENT_REMINDER);
    $component->call('toggle', Automation::TYPE_APPOINTMENT_REMINDER);

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_APPOINTMENT_REMINDER)->first();

    expect($automation->active)->toBeFalse();
});

it('mostra un toast e non crea nulla per un utente senza tenant (super-admin)', function () {
    $admin = User::create([
        'tenant_id' => null,
        'name' => 'Super',
        'email' => 'super@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    $this->actingAs($admin);

    Livewire::test(Automations::class)
        ->call('toggle', Automation::TYPE_WELCOME)
        ->assertDispatched('toast');

    expect(Automation::withoutGlobalScopes()->count())->toBe(0);
});

it('blocca l\'attivazione oltre il limite di automazioni del piano', function () {
    // Tenant senza abbonamento → piano default Starter (1 automazione attiva).
    $this->actingAs($this->owner);

    $component = Livewire::test(Automations::class);
    $component->call('toggle', Automation::TYPE_WELCOME);
    $component->call('toggle', Automation::TYPE_APPOINTMENT_REMINDER)
        ->assertDispatched('toast');

    expect(Automation::withoutGlobalScopes()->where('active', true)->count())->toBe(1);
});

it('ignora tipi di flusso sconosciuti', function () {
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)->call('toggle', 'inesistente');

    expect(Automation::withoutGlobalScopes()->count())->toBe(0);
});
