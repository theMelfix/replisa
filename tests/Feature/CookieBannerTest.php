<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra il banner cookie sulla landing', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('replisa-cookie-banner', false)
        ->assertSee('cookie tecnici');
});

it('mostra il banner cookie sulle pagine legali', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee('replisa-cookie-banner', false);
});

it('mostra il banner cookie sulle pagine di autenticazione', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('replisa-cookie-banner', false);
});

it('il banner rimanda alla sezione cookie della privacy', function () {
    $this->get('/')->assertSee('/privacy#cookie', false);
});

it('la privacy descrive l\'uso dei soli cookie tecnici', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee('id="cookie"', false)
        ->assertSee('cookie tecnici');
});

it('mostra il banner anche nell\'app autenticata', function () {
    $this->seed(RoleSeeder::class);
    $tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $owner = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Owner', 'email' => 'owner@example.com', 'password' => bcrypt('password'),
    ]);
    $owner->assignRole(User::ROLE_OWNER);

    $this->actingAs($owner)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('replisa-cookie-banner', false);
});
