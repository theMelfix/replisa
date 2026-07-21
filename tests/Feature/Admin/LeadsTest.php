<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Leads;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function leadSuperAdmin(): User
{
    $user = User::create(['tenant_id' => null, 'name' => 'Boss', 'email' => 'boss@example.com', 'password' => bcrypt('password')]);
    $user->assignRole(User::ROLE_SUPER_ADMIN);

    return $user;
}

function makeLead(array $attrs = []): Lead
{
    return Lead::create(array_merge([
        'name' => 'Mario Rossi',
        'email' => 'mario@studio.it',
        'business' => 'Studio dentistico',
        'status' => Lead::STATUS_NEW,
    ], $attrs));
}

it('un owner non accede alle richieste demo admin', function () {
    $tenant = Tenant::create(['name' => 'A']);
    $owner = User::create(['tenant_id' => $tenant->id, 'name' => 'O', 'email' => 'o@example.com', 'password' => bcrypt('password')]);
    $owner->assignRole(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/admin/leads')->assertForbidden();
});

it('un ospite viene reindirizzato al login', function () {
    $this->get('/admin/leads')->assertRedirect(route('login'));
});

it('il super-admin vede i lead', function () {
    makeLead(['name' => 'Anna Verdi']);
    $this->actingAs(leadSuperAdmin());

    $this->get('/admin/leads')->assertOk()->assertSee('Anna Verdi');
});

it('filtra i lead per stato', function () {
    makeLead(['name' => 'Nuovo Lead', 'status' => Lead::STATUS_NEW]);
    makeLead(['name' => 'Vecchio Lead', 'status' => Lead::STATUS_ARCHIVED]);

    $this->actingAs(leadSuperAdmin());

    Livewire::test(Leads::class)
        ->set('status', Lead::STATUS_ARCHIVED)
        ->assertSee('Vecchio Lead')
        ->assertDontSee('Nuovo Lead');
});

it('cerca i lead per nome, email o attività', function () {
    makeLead(['name' => 'Mario', 'email' => 'mario@x.it', 'business' => 'Palestra']);
    makeLead(['name' => 'Luigi', 'email' => 'luigi@y.it', 'business' => 'Ristorante']);

    $this->actingAs(leadSuperAdmin());

    Livewire::test(Leads::class)
        ->set('search', 'Palestra')
        ->assertSee('Mario')
        ->assertDontSee('Luigi');
});

it('avanza lo stato di un lead', function () {
    $lead = makeLead();
    $this->actingAs(leadSuperAdmin());

    Livewire::test(Leads::class)
        ->call('setStatus', $lead->id, Lead::STATUS_CONTACTED)
        ->assertDispatched('toast');

    expect($lead->fresh()->status)->toBe(Lead::STATUS_CONTACTED);
});

it('ignora uno stato non valido', function () {
    $lead = makeLead();
    $this->actingAs(leadSuperAdmin());

    Livewire::test(Leads::class)->call('setStatus', $lead->id, 'inesistente');

    expect($lead->fresh()->status)->toBe(Lead::STATUS_NEW);
});

it('elimina un lead', function () {
    $lead = makeLead();
    $this->actingAs(leadSuperAdmin());

    Livewire::test(Leads::class)
        ->call('delete', $lead->id)
        ->assertDispatched('toast');

    expect(Lead::find($lead->id))->toBeNull();
});
