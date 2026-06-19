<?php

use App\Livewire\Dashboard;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function ownerForTenant(Tenant $tenant): User
{
    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Owner '.$tenant->id,
        'email' => 'owner'.$tenant->id.'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole(User::ROLE_OWNER);

    return $user;
}

it('reindirizza gli ospiti al login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('mostra la dashboard a un owner autenticato', function () {
    $tenant = Tenant::create(['name' => 'Studio A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->actingAs(ownerForTenant($tenant));

    $this->get('/dashboard')->assertOk()->assertSee('Studio A');
});

it('conta solo i messaggi del proprio tenant', function () {
    $a = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $b = Tenant::create(['name' => 'B', 'phone_number_id' => 'PB', 'access_token' => 'TB']);

    $ca = $a->contacts()->create(['phone' => '391']);
    $cb = $b->contacts()->create(['phone' => '392']);

    $a->messages()->create(['contact_id' => $ca->id, 'direction' => Message::DIRECTION_OUTBOUND, 'type' => 'text', 'status' => 'sent']);
    $a->messages()->create(['contact_id' => $ca->id, 'direction' => Message::DIRECTION_OUTBOUND, 'type' => 'text', 'status' => 'sent']);
    $b->messages()->create(['contact_id' => $cb->id, 'direction' => Message::DIRECTION_OUTBOUND, 'type' => 'text', 'status' => 'sent']);

    $this->actingAs(ownerForTenant($a));

    Livewire\Livewire::test(Dashboard::class)
        ->assertViewHas('inviati', 2);
});
