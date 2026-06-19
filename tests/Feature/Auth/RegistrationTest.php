<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    $component = Volt::test('pages.auth.register')
        ->set('business_name', 'Studio Rossi')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration creates a tenant and makes the user its owner', function () {
    Volt::test('pages.auth.register')
        ->set('business_name', 'Studio Rossi')
        ->set('name', 'Giovanni')
        ->set('email', 'owner@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $tenant = Tenant::where('name', 'Studio Rossi')->first();
    $user = User::where('email', 'owner@example.com')->first();

    expect($tenant)->not->toBeNull()
        ->and($user->tenant_id)->toBe($tenant->id)
        ->and($user->hasRole(User::ROLE_OWNER))->toBeTrue();
});

test('business name is required', function () {
    Volt::test('pages.auth.register')
        ->set('business_name', '')
        ->set('name', 'Giovanni')
        ->set('email', 'owner@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['business_name' => 'required']);

    $this->assertGuest();
});
