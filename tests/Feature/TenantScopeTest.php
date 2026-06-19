<?php

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenantA = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->tenantB = Tenant::create(['name' => 'B', 'phone_number_id' => 'PB', 'access_token' => 'TB']);

    $this->tenantA->contacts()->create(['phone' => '391', 'name' => 'Anna']);
    $this->tenantB->contacts()->create(['phone' => '392', 'name' => 'Bruno']);
});

function ownerOf(Tenant $tenant): User
{
    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Owner',
        'email' => 'owner'.$tenant->id.'@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole(User::ROLE_OWNER);

    return $user;
}

it('un owner vede solo i contatti del proprio tenant', function () {
    $this->actingAs(ownerOf($this->tenantA));

    expect(Contact::pluck('name')->all())->toBe(['Anna']);
});

it('un super-admin vede i contatti di tutti i tenant', function () {
    $admin = User::create(['name' => 'Boss', 'email' => 'boss@example.com', 'password' => bcrypt('password')]);
    $admin->assignRole(User::ROLE_SUPER_ADMIN);
    $this->actingAs($admin);

    expect(Contact::count())->toBe(2);
});

it('senza utente autenticato (console/webhook) lo scope è no-op', function () {
    expect(Contact::count())->toBe(2);
});

it('compila automaticamente tenant_id alla creazione nel contesto di un owner', function () {
    $this->actingAs(ownerOf($this->tenantA));

    $contact = Contact::create(['phone' => '393', 'name' => 'Carla']);

    expect($contact->tenant_id)->toBe($this->tenantA->id);
});
