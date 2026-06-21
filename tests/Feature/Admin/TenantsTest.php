<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Tenants;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PlanLimits;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeUser(string $role, ?Tenant $tenant = null): User
{
    $user = User::create([
        'tenant_id' => $tenant?->id,
        'name' => 'U',
        'email' => $role.'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);

    return $user;
}

it('un super-admin può vedere la lista tenant', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));

    $this->get('/admin/tenants')->assertOk();
});

it('un owner non può accedere all\'area admin', function () {
    $tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->actingAs(makeUser(User::ROLE_OWNER, $tenant));

    $this->get('/admin/tenants')->assertForbidden();
});

it('un ospite viene rediretto al login', function () {
    $this->get('/admin/tenants')->assertRedirect(route('login'));
});

it('il super-admin può attivare/disattivare un tenant', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));
    $tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA', 'active' => true]);

    Livewire::test(Tenants::class)->call('toggle', $tenant->id);

    expect($tenant->fresh()->active)->toBeFalse();
});

it('il super-admin assegna una licenza offline con scadenza', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));
    $tenant = Tenant::create(['name' => 'A']);

    Livewire::test(Tenants::class)
        ->set("licensePlan.{$tenant->id}", 'pro')
        ->set("licenseExpiry.{$tenant->id}", '2027-01-31')
        ->call('assignLicense', $tenant->id)
        ->assertDispatched('toast');

    $tenant->refresh();
    expect($tenant->manual_plan)->toBe('pro')
        ->and($tenant->manual_plan_expires_at->format('Y-m-d'))->toBe('2027-01-31')
        ->and(PlanLimits::for($tenant)->planKey())->toBe('pro')
        ->and(PlanLimits::for($tenant)->planSource())->toBe('offline');
});

it('rifiuta un piano non valido per la licenza offline', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));
    $tenant = Tenant::create(['name' => 'A']);

    Livewire::test(Tenants::class)
        ->set("licensePlan.{$tenant->id}", 'inesistente')
        ->call('assignLicense', $tenant->id)
        ->assertDispatched('toast');

    expect($tenant->fresh()->manual_plan)->toBeNull();
});

it('revoca la licenza offline', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));
    $tenant = Tenant::create(['name' => 'A', 'manual_plan' => 'pro']);

    Livewire::test(Tenants::class)->call('revokeLicense', $tenant->id);

    expect($tenant->fresh()->manual_plan)->toBeNull();
});

it('una licenza offline scaduta non è attiva', function () {
    $tenant = Tenant::create(['name' => 'A', 'manual_plan' => 'pro', 'manual_plan_expires_at' => now()->subDay()]);

    expect($tenant->hasActiveOfflineLicense())->toBeFalse()
        ->and(PlanLimits::for($tenant)->planKey())->toBe('starter'); // default
});

it('disdetta senza abbonamento Stripe mostra un toast', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));
    $tenant = Tenant::create(['name' => 'A']);

    Livewire::test(Tenants::class)->call('cancelSubscription', $tenant->id)->assertDispatched('toast');
});

it('rimborso senza cliente Stripe mostra un toast', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));
    $tenant = Tenant::create(['name' => 'A']);

    Livewire::test(Tenants::class)->call('refundLast', $tenant->id)->assertDispatched('toast');
});

it('un tenant bloccato viene rediretto alla pagina sospeso', function () {
    $tenant = Tenant::create(['name' => 'A', 'active' => false]);
    $this->actingAs(makeUser(User::ROLE_OWNER, $tenant));

    $this->get('/dashboard')->assertRedirect(route('suspended'));
});

it('un tenant attivo accede normalmente', function () {
    $tenant = Tenant::create(['name' => 'A', 'active' => true]);
    $this->actingAs(makeUser(User::ROLE_OWNER, $tenant));

    $this->get('/dashboard')->assertOk();
});

it('il super-admin crea un nuovo cliente con owner e licenza offline', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));

    Livewire::test(Tenants::class)
        ->set('newBusinessName', 'Nuovo Studio')
        ->set('newOwnerName', 'Mario')
        ->set('newOwnerEmail', 'mario@example.com')
        ->set('newPassword', 'password-123')
        ->set('newPlan', 'base')
        ->call('createTenant')
        ->assertDispatched('toast');

    $tenant = Tenant::where('name', 'Nuovo Studio')->first();
    $owner = User::where('email', 'mario@example.com')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->manual_plan)->toBe('base')
        ->and($owner)->not->toBeNull()
        ->and($owner->tenant_id)->toBe($tenant->id)
        ->and($owner->hasRole(User::ROLE_OWNER))->toBeTrue();
});

it('la creazione cliente richiede ragione sociale ed email', function () {
    $this->actingAs(makeUser(User::ROLE_SUPER_ADMIN));

    Livewire::test(Tenants::class)
        ->set('newBusinessName', '')
        ->set('newOwnerEmail', '')
        ->call('createTenant')
        ->assertHasErrors(['newBusinessName', 'newOwnerEmail']);
});

it('il command crea un super-admin', function () {
    $this->artisan('replisa:create-admin', ['email' => 'boss@example.com'])
        ->expectsQuestion('Nome', 'Boss')
        ->expectsQuestion('Password', 'secret-password')
        ->assertSuccessful();

    $user = User::where('email', 'boss@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->isSuperAdmin())->toBeTrue()
        ->and($user->tenant_id)->toBeNull();
});
