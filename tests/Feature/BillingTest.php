<?php

use App\Livewire\Billing;
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
    $this->get('/billing')->assertRedirect(route('login'));
});

it('mostra i quattro piani a un owner', function () {
    $this->actingAs($this->owner);

    $this->get('/billing')
        ->assertOk()
        ->assertSee('Starter')
        ->assertSee('Base')
        ->assertSee('Pro')
        ->assertSee('Business');
});

it('mostra un toast se il piano non ha un price id configurato', function () {
    // In ambiente di test le STRIPE_PRICE_* non sono valorizzate.
    $this->actingAs($this->owner);

    Livewire::test(Billing::class)
        ->call('subscribe', 'base')
        ->assertDispatched('toast');

    expect($this->tenant->fresh()->subscriptions()->count())->toBe(0);
});

it('non mostra il link abbonamento al super-admin', function () {
    $admin = User::create([
        'tenant_id' => null,
        'name' => 'Super',
        'email' => 'super@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee(route('billing'));
});
