<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Tenants;
use App\Models\Tenant;
use App\Models\User;
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
