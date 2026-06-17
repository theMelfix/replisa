<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.meta.sandbox.phone_number_id' => 'PNID-SANDBOX',
        'services.meta.sandbox.waba_id' => 'WABA-SANDBOX',
        'services.meta.sandbox.access_token' => 'SANDBOX-TOKEN',
    ]);
});

it('crea un tenant dalle credenziali sandbox del .env', function () {
    $this->artisan('replisa:seed-sandbox-tenant')->assertSuccessful();

    $tenant = Tenant::where('phone_number_id', 'PNID-SANDBOX')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->active)->toBeTrue()
        ->and($tenant->waba_id)->toBe('WABA-SANDBOX')
        ->and($tenant->access_token)->toBe('SANDBOX-TOKEN'); // cast encrypted: round-trip
});

it('è idempotente e aggiorna il tenant esistente', function () {
    $this->artisan('replisa:seed-sandbox-tenant')->assertSuccessful();
    $this->artisan('replisa:seed-sandbox-tenant')->assertSuccessful();

    expect(Tenant::where('phone_number_id', 'PNID-SANDBOX')->count())->toBe(1);
});

it('fallisce se mancano le credenziali sandbox', function () {
    config(['services.meta.sandbox.access_token' => null]);

    $this->artisan('replisa:seed-sandbox-tenant')->assertFailed();
});
