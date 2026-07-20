<?php

use App\Models\Contact;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create([
        'name' => 'Studio A',
        'phone_number_id' => 'PNID-1',
        'waba_id' => 'WABA-1',
        'access_token' => 'TENANT-TOKEN',
    ]);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

/** Fake di una risposta Meta OK. Va chiamato dal singolo test (una fake per URL). */
function fakeMetaOk(): void
{
    Http::fake(['graph.facebook.com/*' => Http::response([
        'messaging_product' => 'whatsapp',
        'messages' => [['id' => 'wamid.OUT']],
    ], 200)]);
}

it('rifiuta le richieste senza token', function () {
    $this->postJson('/api/v1/messages/send', [])->assertUnauthorized();
});

it('invia un template e logga il messaggio per il tenant', function () {
    fakeMetaOk();
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/messages/send', [
        'to' => '39 333 444 5566',
        'type' => 'template',
        'template' => 'appointment_reminder',
        'language' => 'it',
        'params' => ['Mario', '24/07/2026', '15:30'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'template')
        ->assertJsonPath('data.contact.phone', '393334445566');

    expect(Message::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count())->toBe(1);

    Http::assertSent(function ($request) {
        return $request['type'] === 'template'
            && $request['template']['name'] === 'appointment_reminder'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Mario';
    });
});

it('invia un messaggio di testo', function () {
    fakeMetaOk();
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/messages/send', [
        'to' => '393334445566',
        'type' => 'text',
        'text' => 'Ciao!',
    ])->assertCreated()->assertJsonPath('data.type', 'text');
});

it('valida i campi obbligatori per tipo', function () {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/messages/send', ['to' => '393334445566', 'type' => 'template'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('template');

    $this->postJson('/api/v1/messages/send', ['to' => '393334445566', 'type' => 'text'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('text');
});

it('restituisce 422 se il tenant non ha collegato WhatsApp', function () {
    $this->tenant->update(['phone_number_id' => null, 'access_token' => null]);
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/messages/send', [
        'to' => '393334445566', 'type' => 'text', 'text' => 'Ciao',
    ])->assertStatus(422);
});

it('rispetta il limite contatti del piano sui numeri nuovi', function () {
    config(['plans.plans.starter.limits.contacts' => 0]);
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/messages/send', [
        'to' => '393339990000', 'type' => 'text', 'text' => 'Ciao',
    ])->assertStatus(422);

    expect(Contact::withoutGlobalScopes()->count())->toBe(0);
});

it('propaga un errore Meta come 502', function () {
    Http::fake(['graph.facebook.com/*' => Http::response([
        'error' => ['message' => 'Fuori finestra', 'code' => 131047],
    ], 400)]);

    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/messages/send', [
        'to' => '393334445566', 'type' => 'text', 'text' => 'Ciao',
    ])->assertStatus(502)->assertJsonPath('meta_code', 131047);
});

it('elenca i messaggi del tenant, non quelli altrui', function () {
    $other = Tenant::create(['name' => 'B', 'phone_number_id' => 'PB', 'access_token' => 'TB']);
    $mine = Contact::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'phone' => '391']);
    $theirs = Contact::withoutGlobalScopes()->create(['tenant_id' => $other->id, 'phone' => '392']);

    Message::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'contact_id' => $mine->id,
        'direction' => 'outbound', 'type' => 'text', 'content' => ['body' => 'x'], 'status' => 'sent',
    ]);
    Message::withoutGlobalScopes()->create([
        'tenant_id' => $other->id, 'contact_id' => $theirs->id,
        'direction' => 'outbound', 'type' => 'text', 'content' => ['body' => 'y'], 'status' => 'sent',
    ]);

    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/messages')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.contact.phone', '391');
});

it('filtra i messaggi per direzione', function () {
    $c = Contact::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'phone' => '391']);
    foreach (['inbound', 'outbound'] as $dir) {
        Message::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id, 'contact_id' => $c->id,
            'direction' => $dir, 'type' => 'text', 'content' => [], 'status' => 'sent',
        ]);
    }

    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/messages?direction=inbound')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.direction', 'inbound');
});

it('blocca l\'accesso API a un tenant sospeso', function () {
    $this->tenant->update(['active' => false]);
    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/messages')->assertStatus(403);
});

it('nega l\'API a un utente senza tenant', function () {
    $admin = User::create(['tenant_id' => null, 'name' => 'S', 'email' => 's@x.it', 'password' => bcrypt('x')]);
    $admin->assignRole(User::ROLE_SUPER_ADMIN);
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/messages/send', [
        'to' => '393334445566', 'type' => 'text', 'text' => 'Ciao',
    ])->assertStatus(403);
});
