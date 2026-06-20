<?php

use App\Livewire\WhatsAppSettings;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'Studio A', 'phone_number_id' => 'PID-1', 'access_token' => 'TOK-1']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/whatsapp')->assertRedirect(route('login'));
});

it('mostra il form precompilato con il phone number id, senza esporre il token', function () {
    $this->actingAs($this->owner);

    Livewire::test(WhatsAppSettings::class)
        ->assertSet('phone_number_id', 'PID-1')
        ->assertSet('access_token', '')
        ->assertSee('Account WhatsApp');
});

it('salva le credenziali e mantiene il token se lasciato vuoto', function () {
    $this->actingAs($this->owner);

    Livewire::test(WhatsAppSettings::class)
        ->set('phone_number_id', 'PID-2')
        ->set('waba_id', 'WABA-2')
        ->set('access_token', '')
        ->call('save')
        ->assertDispatched('toast');

    $this->tenant->refresh();
    expect($this->tenant->phone_number_id)->toBe('PID-2')
        ->and($this->tenant->waba_id)->toBe('WABA-2')
        ->and($this->tenant->access_token)->toBe('TOK-1');
});

it('aggiorna il token quando fornito', function () {
    $this->actingAs($this->owner);

    Livewire::test(WhatsAppSettings::class)
        ->set('phone_number_id', 'PID-1')
        ->set('access_token', 'TOK-2')
        ->call('save');

    expect($this->tenant->refresh()->access_token)->toBe('TOK-2');
});

it('richiede il phone number id', function () {
    $this->actingAs($this->owner);

    Livewire::test(WhatsAppSettings::class)
        ->set('phone_number_id', '')
        ->call('save')
        ->assertHasErrors(['phone_number_id' => 'required']);
});

it('verifica la connessione contro la Graph API di Meta', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['display_phone_number' => '+39 333 1234567', 'verified_name' => 'Studio A'], 200),
    ]);

    $this->actingAs($this->owner);

    Livewire::test(WhatsAppSettings::class)
        ->call('verify')
        ->assertDispatched('toast');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'PID-1'));
});

it('segnala il fallimento della verifica', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401),
    ]);

    $this->actingAs($this->owner);

    Livewire::test(WhatsAppSettings::class)
        ->call('verify')
        ->assertDispatched('toast');
});
