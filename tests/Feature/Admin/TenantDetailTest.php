<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\TenantDetail;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantInvitation;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/** Super-admin di comodo per questi test. */
function detailAdmin(): User
{
    $admin = User::create([
        'tenant_id' => null,
        'name' => 'Admin',
        'email' => 'detail-admin@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    return $admin;
}

/** Cliente con un owner che non ha ancora attivato l'account. */
function detailTenant(string $name = 'Studio Rossi'): Tenant
{
    static $seq = 0;
    $seq++;

    $tenant = Tenant::create([
        'name' => $name,
        'sector' => 'studio_medico',
        // phone_number_id è unique: un valore fisso farebbe collidere due
        // clienti creati nello stesso test.
        'phone_number_id' => 'PN-'.$seq,
        'access_token' => 'TOKEN-ORIGINALE',
    ]);

    $owner = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mario Rossi',
        'email' => 'mario-'.$tenant->id.'@example.com',
        'password' => bcrypt('password'),
    ]);
    $owner->assignRole(User::ROLE_OWNER);

    return $tenant;
}

it('non è raggiungibile da un owner', function () {
    $tenant = detailTenant();
    $this->actingAs($tenant->users()->first());

    $this->get("/admin/tenants/{$tenant->id}")->assertForbidden();
});

it('mostra anagrafica, utenti e stato del collegamento WhatsApp', function () {
    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    $this->get("/admin/tenants/{$tenant->id}")
        ->assertOk()
        ->assertSee('Studio Rossi')
        ->assertSee('Mario Rossi')
        ->assertSee('Invito in sospeso')
        ->assertSee('Collegato');
});

it('salva anagrafica e dati fiscali normalizzando provincia e codice SDI', function () {
    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->set('name', 'Studio Rossi SRL')
        ->set('vat_number', '01809180886')
        ->set('city', 'Vittoria')
        ->set('postal_code', '97019')
        ->set('province', 'rg')
        ->set('sdi_code', 'abc123')
        ->set('pec', 'studio@pec.it')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $tenant->refresh();

    expect($tenant->name)->toBe('Studio Rossi SRL')
        ->and($tenant->vat_number)->toBe('01809180886')
        ->and($tenant->province)->toBe('RG')
        ->and($tenant->sdi_code)->toBe('ABC123')
        ->and($tenant->pec)->toBe('studio@pec.it');
});

it('riporta a NULL i campi facoltativi lasciati vuoti', function () {
    // '' su vat_number, che è unique, farebbe collidere due clienti senza P.IVA.
    $tenant = detailTenant();
    $tenant->update(['vat_number' => '01809180886', 'city' => 'Vittoria']);
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->set('vat_number', '')
        ->set('city', '')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $tenant->refresh();

    expect($tenant->vat_number)->toBeNull()
        ->and($tenant->city)->toBeNull();
});

it('rifiuta una Partita IVA con cifra di controllo errata', function () {
    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->set('vat_number', '01809180880')
        ->call('saveProfile')
        ->assertHasErrors('vat_number');
});

it('accetta il salvataggio con la Partita IVA che il cliente ha già', function () {
    // Regressione: la regola unique deve ignorare il tenant stesso, altrimenti
    // non si può più correggere nemmeno la ragione sociale.
    $tenant = detailTenant();
    $tenant->update(['vat_number' => '01809180886']);
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->set('name', 'Nuovo Nome')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($tenant->refresh()->name)->toBe('Nuovo Nome');
});

it('rifiuta una Partita IVA già usata da un altro cliente', function () {
    $altro = detailTenant('Altro Studio');
    $altro->update(['vat_number' => '01809180886']);

    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->set('vat_number', '01809180886')
        ->call('saveProfile')
        ->assertHasErrors('vat_number');
});

it('salva le credenziali WhatsApp mantenendo il token se il campo resta vuoto', function () {
    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->set('phone_number_id', 'PN-2')
        ->set('waba_id', 'WABA-2')
        ->set('access_token', '')
        ->call('saveWhatsApp')
        ->assertHasNoErrors();

    $tenant->refresh();

    expect($tenant->phone_number_id)->toBe('PN-2')
        ->and($tenant->waba_id)->toBe('WABA-2')
        ->and($tenant->access_token)->toBe('TOKEN-ORIGINALE');
});

it('sostituisce il token quando ne viene inserito uno nuovo', function () {
    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->set('access_token', 'TOKEN-NUOVO')
        ->call('saveWhatsApp')
        ->assertHasNoErrors()
        ->assertSet('access_token', '');

    expect($tenant->refresh()->access_token)->toBe('TOKEN-NUOVO');
});

it('verifica il collegamento interrogando Meta', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['display_phone_number' => '+39 333 1234567'], 200),
    ]);

    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->call('verifyWhatsApp')
        ->assertDispatched('toast', type: 'success');
});

it('segnala il fallimento della verifica riportando il messaggio di Meta', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401),
    ]);

    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->call('verifyWhatsApp')
        ->assertDispatched('toast', type: 'error');
});

it('rispedisce l\'invito a chi non ha ancora attivato l\'account', function () {
    Notification::fake();

    $tenant = detailTenant();
    $owner = $tenant->users()->first();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->call('resendInvitation', $owner->id)
        ->assertDispatched('toast', type: 'success');

    Notification::assertSentTo($owner, TenantInvitation::class);
});

it('non rispedisce l\'invito a chi ha già attivato', function () {
    Notification::fake();

    $tenant = detailTenant();
    $owner = $tenant->users()->first();
    $owner->forceFill(['email_verified_at' => now()])->save();
    $this->actingAs(detailAdmin());

    Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->call('resendInvitation', $owner->id)
        ->assertDispatched('toast', type: 'info');

    Notification::assertNothingSent();
});

it('non rispedisce l\'invito a un utente di un altro cliente', function () {
    Notification::fake();

    $altro = detailTenant('Altro Studio');
    $estraneo = $altro->users()->first();

    $tenant = detailTenant();
    $this->actingAs(detailAdmin());

    // La ricerca passa dalla relazione del tenant: un id estraneo non esiste
    // proprio, e in HTTP diventa un 404.
    expect(fn () => Livewire::test(TenantDetail::class, ['tenant' => $tenant])
        ->call('resendInvitation', $estraneo->id))
        ->toThrow(ModelNotFoundException::class);

    Notification::assertNothingSent();
});
