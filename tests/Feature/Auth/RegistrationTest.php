<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    // Nessuna chiamata reale: i test che arrivano a VIES impostano il loro fake
    // (ri-chiamare Http::fake non sovrascrive uno stub già registrato).
    Http::preventStrayRequests();
});

/** Stub VIES che risponde "P.IVA valida". */
function fakeViesValid(): void
{
    Http::fake(['ec.europa.eu/*' => Http::response(['valid' => true], 200)]);
}

/** Dati di registrazione validi (P.IVA con checksum corretto). */
function validRegistrationData(): array
{
    return [
        'business_name' => 'Studio Rossi',
        'sector' => 'commercialista',
        'vat_number' => '01809180886',
        'tax_code' => '',
        'address' => 'Via Roma 1',
        'city' => 'Vittoria',
        'postal_code' => '97019',
        'province' => 'RG',
        'sdi_code' => '',
        'pec' => 'studio@pec.it',
        'name' => 'Giovanni',
        'email' => 'owner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
}

function registerComponent(array $overrides = [])
{
    $component = Volt::test('pages.auth.register');

    foreach (array_merge(validRegistrationData(), $overrides) as $key => $value) {
        $component->set($key, $value);
    }

    return $component;
}

test('registration screen can be rendered', function () {
    $this->get('/register')->assertOk()->assertSeeVolt('pages.auth.register');
});

test('new users can register with valid fiscal data', function () {
    fakeViesValid();

    registerComponent()->call('register')->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration creates a tenant with fiscal data and validated vat', function () {
    fakeViesValid();

    registerComponent()->call('register');

    $tenant = Tenant::where('vat_number', '01809180886')->first();
    $user = User::where('email', 'owner@example.com')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->name)->toBe('Studio Rossi')
        ->and($tenant->province)->toBe('RG')
        ->and($tenant->pec)->toBe('studio@pec.it')
        ->and($tenant->vat_validated_at)->not->toBeNull()
        ->and($user->tenant_id)->toBe($tenant->id)
        ->and($user->hasRole(User::ROLE_OWNER))->toBeTrue();
});

test('business name is required', function () {
    registerComponent(['business_name' => ''])
        ->call('register')
        ->assertHasErrors(['business_name' => 'required']);

    $this->assertGuest();
});

test('vat number with invalid checksum is rejected', function () {
    registerComponent(['vat_number' => '01809180880']) // ultima cifra errata
        ->call('register')
        ->assertHasErrors('vat_number');

    $this->assertGuest();
});

test('vat number rejected when VIES says it does not exist', function () {
    Http::fake(['ec.europa.eu/*' => Http::response(['valid' => false], 200)]);

    registerComponent()
        ->call('register')
        ->assertHasErrors('vat_number');

    $this->assertGuest();
});

test('vat number must be unique', function () {
    Tenant::create(['name' => 'Esistente', 'vat_number' => '01809180886']);

    registerComponent()
        ->call('register')
        ->assertHasErrors('vat_number');
});

test('either sdi code or pec is required', function () {
    registerComponent(['sdi_code' => '', 'pec' => ''])
        ->call('register')
        ->assertHasErrors(['sdi_code', 'pec']);
});

test('registration proceeds unvalidated when VIES is unreachable', function () {
    Http::fake(['ec.europa.eu/*' => Http::response('', 500)]);

    registerComponent()->call('register')->assertRedirect(route('dashboard', absolute: false));

    expect(Tenant::where('vat_number', '01809180886')->first()->vat_validated_at)->toBeNull();
});
