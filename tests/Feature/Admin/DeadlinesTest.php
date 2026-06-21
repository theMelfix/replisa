<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Deadlines;
use App\Models\Deadline;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function superAdmin(): User
{
    $user = User::create(['tenant_id' => null, 'name' => 'Boss', 'email' => 'boss@example.com', 'password' => bcrypt('password')]);
    $user->assignRole(User::ROLE_SUPER_ADMIN);

    return $user;
}

it('un owner non accede alle scadenze nazionali admin', function () {
    $tenant = Tenant::create(['name' => 'A']);
    $owner = User::create(['tenant_id' => $tenant->id, 'name' => 'O', 'email' => 'o@example.com', 'password' => bcrypt('password')]);
    $owner->assignRole(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/admin/deadlines')->assertForbidden();
});

it('il super-admin aggiunge una scadenza nazionale', function () {
    $this->actingAs(superAdmin());

    Livewire::test(Deadlines::class)
        ->set('name', 'IMU 2027 — acconto')
        ->set('due_date', '2027-06-16')
        ->call('add')
        ->assertDispatched('toast');

    expect(Deadline::national()->where('name', 'IMU 2027 — acconto')->exists())->toBeTrue();
});

it('il super-admin aggiorna la data e elimina', function () {
    $this->actingAs(superAdmin());
    $deadline = Deadline::create(['tenant_id' => null, 'name' => 'X', 'due_date' => '2027-01-01', 'active' => true]);

    Livewire::test(Deadlines::class)
        ->set("dates.{$deadline->id}", '2027-02-02')
        ->call('updateDate', $deadline->id);

    expect($deadline->fresh()->due_date->format('Y-m-d'))->toBe('2027-02-02');

    Livewire::test(Deadlines::class)->call('delete', $deadline->id);

    expect(Deadline::find($deadline->id))->toBeNull();
});
