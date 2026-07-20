<?php

use App\Livewire\ApiTokens;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner', 'email' => 'owner@example.com', 'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/api-tokens')->assertRedirect(route('login'));
});

it('genera una chiave e la mostra una sola volta', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(ApiTokens::class)
        ->set('name', 'Gestionale')
        ->call('create')
        ->assertHasNoErrors();

    expect($component->get('plainTextToken'))->toBeString()->not->toBeEmpty();
    expect($this->owner->tokens()->count())->toBe(1);
    expect($this->owner->tokens()->first()->name)->toBe('Gestionale');
});

it('richiede un nome', function () {
    $this->actingAs($this->owner);

    Livewire::test(ApiTokens::class)
        ->set('name', '')
        ->call('create')
        ->assertHasErrors('name');

    expect($this->owner->tokens()->count())->toBe(0);
});

it('revoca una chiave', function () {
    $this->actingAs($this->owner);
    $token = $this->owner->createToken('Vecchia');

    Livewire::test(ApiTokens::class)
        ->call('revoke', $token->accessToken->id);

    expect($this->owner->tokens()->count())->toBe(0);
});

it('non può revocare la chiave di un altro utente', function () {
    $other = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Altro', 'email' => 'altro@example.com', 'password' => bcrypt('password'),
    ]);
    $otherToken = $other->createToken('Sua');

    $this->actingAs($this->owner);

    Livewire::test(ApiTokens::class)
        ->call('revoke', $otherToken->accessToken->id);

    expect($other->tokens()->count())->toBe(1);
});

it('mostra la pagina di documentazione API', function () {
    $this->actingAs($this->owner);

    $this->get('/api-docs')
        ->assertOk()
        ->assertSee('/messages/send')
        ->assertSee(url('/api/v1'));
});
