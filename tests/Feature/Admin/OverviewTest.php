<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Overview;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function overviewAdmin(): User
{
    $admin = User::create([
        'tenant_id' => null,
        'name' => 'Admin',
        'email' => 'overview-admin@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    return $admin;
}

function overviewTenant(string $name, array $attributes = []): Tenant
{
    static $seq = 0;
    $seq++;

    return Tenant::create(array_merge([
        'name' => $name,
        // phone_number_id è unique: un valore fisso farebbe collidere due clienti.
        'phone_number_id' => 'PN-OV-'.$seq,
        'access_token' => 'TOKEN',
    ], $attributes));
}

/** Messaggio fallito, con il motivo nella forma che arriva da Meta sull'invio. */
function failedMessage(Tenant $tenant, array $error, ?string $when = null): Message
{
    $contact = $tenant->contacts()->create(['phone' => '3900'.random_int(10000, 99999)]);

    $message = $tenant->messages()->create([
        'contact_id' => $contact->id,
        'direction' => Message::DIRECTION_OUTBOUND,
        'type' => Message::TYPE_TEMPLATE,
        'status' => Message::STATUS_FAILED,
        'error' => $error,
    ]);

    if ($when) {
        $message->forceFill(['created_at' => $when])->save();
    }

    return $message;
}

it('non è raggiungibile da un owner', function () {
    $tenant = overviewTenant('Studio A');
    $owner = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Owner',
        'email' => 'owner-ov@example.com',
        'password' => bcrypt('password'),
    ]);
    $owner->assignRole(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/admin')->assertForbidden();
});

it('è la home del super-admin', function () {
    $this->actingAs(overviewAdmin());

    $this->get('/admin')->assertOk()->assertSee('Panoramica');
});

it('conta clienti attivi e bloccati su tutta la piattaforma', function () {
    overviewTenant('Attivo 1');
    overviewTenant('Attivo 2');
    overviewTenant('Bloccato', ['active' => false]);

    $this->actingAs(overviewAdmin());

    Livewire::test(Overview::class)
        ->assertViewHas('activeCount', 2)
        ->assertViewHas('blockedCount', 1);
});

it('elenca i messaggi falliti recenti con cliente e motivo', function () {
    $tenant = overviewTenant('Studio Rossi');
    failedMessage($tenant, ['message' => 'Invalid OAuth access token', 'code' => 190]);

    $this->actingAs(overviewAdmin());

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Messaggi falliti')
        ->assertSee('Studio Rossi')
        ->assertSee('[190] Invalid OAuth access token');
});

it('ignora i messaggi falliti fuori dalla finestra di osservazione', function () {
    $tenant = overviewTenant('Studio Vecchio');
    failedMessage(
        $tenant,
        ['message' => 'Errore antico'],
        now()->subDays(Overview::FAILURE_DAYS + 1)->toDateTimeString(),
    );

    $this->actingAs(overviewAdmin());

    Livewire::test(Overview::class)->assertViewHas('failuresCount', 0);
});

it('segnala i job falliti', function () {
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\SendCampaign']),
        'exception' => 'Qualcosa è andato storto',
        'failed_at' => now(),
    ]);

    $this->actingAs(overviewAdmin());

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Job falliti')
        ->assertSee('SendCampaign');
});

it('elenca i clienti senza WhatsApp collegato', function () {
    overviewTenant('Collegato');
    overviewTenant('Scollegato', ['phone_number_id' => null, 'access_token' => null]);

    $this->actingAs(overviewAdmin());

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Clienti senza WhatsApp collegato')
        ->assertSee('Scollegato');
});

it('elenca le prove in scadenza e ignora quelle lontane', function () {
    overviewTenant('In scadenza', ['trial_ends_at' => now()->addDays(3)]);
    overviewTenant('Ancora lunga', ['trial_ends_at' => now()->addDays(30)]);

    $this->actingAs(overviewAdmin());

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Prove in scadenza')
        ->assertSee('In scadenza')
        ->assertDontSee('Ancora lunga');
});

it('segnala le licenze offline scadute', function () {
    overviewTenant('Licenza morta', [
        'manual_plan' => 'pro',
        'manual_plan_expires_at' => now()->subDay(),
    ]);

    $this->actingAs(overviewAdmin());

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Licenze offline scadute')
        ->assertSee('Licenza morta');
});

it('dichiara che è tutto in ordine quando non c\'è nessun segnale', function () {
    overviewTenant('Cliente sereno');

    $this->actingAs(overviewAdmin());

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Tutto in ordine')
        ->assertDontSee('Messaggi falliti');
});
