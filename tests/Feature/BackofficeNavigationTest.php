<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function backofficeOwner(): User
{
    $tenant = Tenant::create([
        'name' => 'Studio Rossi',
        'phone_number_id' => 'PN1',
        'access_token' => 'TK1',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mario Rossi',
        'email' => 'mario@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole(User::ROLE_OWNER);

    return $user;
}

function backofficeAdmin(): User
{
    $user = User::create([
        'tenant_id' => null,
        'name' => 'Giovanni',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole(User::ROLE_SUPER_ADMIN);

    return $user;
}

it('mostra al cliente la sua navigazione, raggruppata per area', function () {
    $this->actingAs(backofficeOwner());

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Studio Rossi')
        ->assertSee('Operatività')
        ->assertSee('Automazioni')
        ->assertSee('Impostazioni')
        ->assertSee(route('contacts'))
        ->assertSee(route('messages'))
        ->assertSee(route('appointments'))
        ->assertSee(route('automations'))
        ->assertSee(route('campaigns'))
        ->assertSee(route('deadlines'))
        ->assertSee(route('whatsapp'))
        ->assertSee(route('api-tokens'))
        ->assertSee(route('billing'));
});

it('non mostra al cliente le voci di piattaforma', function () {
    $this->actingAs(backofficeOwner());

    $this->get('/dashboard')
        ->assertOk()
        ->assertDontSee(route('admin.tenants'))
        ->assertDontSee(route('admin.leads'))
        ->assertDontSee(route('admin.deadlines'));
});

it('mostra al super-admin solo la navigazione di piattaforma', function () {
    $this->actingAs(backofficeAdmin());

    $this->get('/admin/tenants')
        ->assertOk()
        ->assertSee('Amministrazione')
        ->assertSee(route('admin.overview'))
        ->assertSee(route('admin.tenants'))
        ->assertSee(route('admin.leads'))
        ->assertSee(route('admin.deadlines'));
});

it('non mostra al super-admin le voci del cliente', function () {
    // Il super-admin non ha un tenant e il TenantScope per lui è un no-op:
    // quelle pagine gli mostrerebbero i dati di tutti i tenant mescolati.
    $this->actingAs(backofficeAdmin());

    $this->get('/admin/tenants')
        ->assertOk()
        ->assertDontSee(route('contacts'))
        ->assertDontSee(route('campaigns'))
        ->assertDontSee(route('billing'))
        ->assertDontSee(route('whatsapp'));
});

it('porta il super-admin dalla dashboard cliente alla sua area', function () {
    $this->actingAs(backofficeAdmin());

    $this->get('/dashboard')->assertRedirect(route('admin.overview'));
});

it('espone i comandi per aprire e chiudere la sidebar su mobile', function () {
    // Regressione: il vecchio menu responsive elencava solo la Dashboard,
    // lasciando le altre pagine irraggiungibili da telefono.
    $this->actingAs(backofficeOwner());

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Apri menu')
        ->assertSee('Chiudi menu');
});
