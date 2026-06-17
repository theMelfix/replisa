<?php

use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\Automation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.meta.graph_version' => 'v25.0']);

    $this->tenant = Tenant::create([
        'name' => 'Pizzeria da Mario',
        'phone_number_id' => 'PNID-1',
        'waba_id' => 'WABA-1',
        'access_token' => 'TENANT-TOKEN',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.OUT']],
        ], 200),
    ]);
});

function activateWelcome(Tenant $tenant, array $config = []): Automation
{
    return $tenant->automations()->create([
        'type' => Automation::TYPE_WELCOME,
        'trigger' => 'inbound_message',
        'config' => $config,
        'active' => true,
    ]);
}

/** Costruisce un evento webhook `messages` per il tenant di test. */
function inbound(array $value): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA-1',
            'changes' => [['field' => 'messages', 'value' => array_merge([
                'metadata' => ['phone_number_id' => 'PNID-1'],
            ], $value)]],
        ]],
    ];
}

function inboundText(string $from, string $body, string $wamid): array
{
    return inbound([
        'contacts' => [['wa_id' => $from, 'profile' => ['name' => 'Cliente']]],
        'messages' => [['from' => $from, 'id' => $wamid, 'type' => 'text', 'text' => ['body' => $body]]],
    ]);
}

it('manda il menu di benvenuto e registra opt-in al primo contatto', function () {
    activateWelcome($this->tenant);

    (new ProcessWhatsAppWebhook(inboundText('393334445566', 'Ciao', 'wamid.IN1')))->handle();

    // Opt-in tracciato (task 3.1.5)
    $contact = $this->tenant->contacts()->sole();
    expect($contact->opted_in)->toBeTrue()
        ->and($contact->opted_in_at)->not->toBeNull();

    // Menu interattivo inviato come reply button (task 3.1.3)
    $sent = Message::where('direction', Message::DIRECTION_OUTBOUND)->sole();
    expect($sent->type)->toBe(Message::TYPE_INTERACTIVE);

    Http::assertSent(function (Request $request) {
        $i = $request->data()['interactive'] ?? null;

        return $i
            && $i['type'] === 'button'
            && count($i['action']['buttons']) === 3
            && $i['action']['buttons'][0]['reply']['id'] === 'welcome:info_servizi';
    });
});

it('non fa nulla se il welcome flow non è attivo', function () {
    // Nessuna automation creata.
    (new ProcessWhatsAppWebhook(inboundText('393334445566', 'Ciao', 'wamid.IN1')))->handle();

    expect(Message::where('direction', Message::DIRECTION_OUTBOUND)->count())->toBe(0);
    expect($this->tenant->contacts()->sole()->opted_in)->toBeFalse();
    Http::assertNothingSent();
});

it('non manda il benvenuto a un contatto già esistente', function () {
    activateWelcome($this->tenant);
    $this->tenant->contacts()->create(['phone' => '393334445566']);

    (new ProcessWhatsAppWebhook(inboundText('393334445566', 'Di nuovo io', 'wamid.IN2')))->handle();

    Http::assertNothingSent();
});

it('non rimanda il benvenuto su un webhook riconsegnato', function () {
    activateWelcome($this->tenant);
    $payload = inboundText('393334445566', 'Ciao', 'wamid.DUP');

    (new ProcessWhatsAppWebhook($payload))->handle();
    (new ProcessWhatsAppWebhook($payload))->handle();

    Http::assertSentCount(1);
});

it('usa il testo dei bottoni e il greeting dal config del tenant', function () {
    activateWelcome($this->tenant, [
        'greeting' => 'Benvenuto da Mario! Scegli:',
        'buttons' => [
            ['id' => 'menu', 'title' => 'Vedi il menu'],
            ['id' => 'orari', 'title' => 'Orari'],
        ],
    ]);

    (new ProcessWhatsAppWebhook(inboundText('393334445566', 'Ciao', 'wamid.IN3')))->handle();

    Http::assertSent(function (Request $request) {
        $i = $request->data()['interactive'] ?? null;

        return $i
            && $i['body']['text'] === 'Benvenuto da Mario! Scegli:'
            && count($i['action']['buttons']) === 2
            && $i['action']['buttons'][0]['reply']['id'] === 'welcome:menu'
            && $i['action']['buttons'][0]['reply']['title'] === 'Vedi il menu';
    });
});

it('instrada la risposta a un bottone del menu (task 3.1.4)', function () {
    activateWelcome($this->tenant, [
        'replies' => ['prenota' => 'Scrivici data e ora per prenotare.'],
    ]);
    // Il contatto esiste già: ha ricevuto il menu in una sessione precedente.
    $this->tenant->contacts()->create(['phone' => '393334445566']);

    (new ProcessWhatsAppWebhook(inbound([
        'messages' => [[
            'from' => '393334445566',
            'id' => 'wamid.BTN',
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button_reply',
                'button_reply' => ['id' => 'welcome:prenota', 'title' => 'Prenota'],
            ],
        ]],
    ])))->handle();

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return ($body['type'] ?? null) === 'text'
            && $body['text']['body'] === 'Scrivici data e ora per prenotare.';
    });
});

it('ignora i button_reply che non appartengono al welcome flow', function () {
    activateWelcome($this->tenant, ['replies' => ['prenota' => 'x']]);
    $this->tenant->contacts()->create(['phone' => '393334445566']);

    (new ProcessWhatsAppWebhook(inbound([
        'messages' => [[
            'from' => '393334445566',
            'id' => 'wamid.OTHER',
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button_reply',
                'button_reply' => ['id' => 'altro_flusso:foo', 'title' => 'Foo'],
            ],
        ]],
    ])))->handle();

    Http::assertNothingSent();
});
