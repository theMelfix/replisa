<?php

use App\Models\Message;
use App\Models\Tenant;
use App\Services\WhatsApp\WhatsAppApiException;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.meta.graph_version' => 'v25.0']);

    $this->tenant = Tenant::create([
        'name' => 'Sandbox',
        'phone_number_id' => '1147055808484414',
        'waba_id' => '1333683675528873',
        'access_token' => 'TENANT-TOKEN',
        'plan' => 'starter',
    ]);

    $this->service = WhatsAppService::for($this->tenant);
});

function fakeAccepted(string $wamid = 'wamid.OK'): void
{
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => $wamid]],
        ], 200),
    ]);
}

it('invia un testo, logga il messaggio e ne traccia il meta_message_id', function () {
    fakeAccepted('wamid.TEXT');

    $message = $this->service->sendText('+39 338 485 2605', 'Ciao!');

    expect($message->status)->toBe(Message::STATUS_SENT)
        ->and($message->direction)->toBe(Message::DIRECTION_OUTBOUND)
        ->and($message->type)->toBe(Message::TYPE_TEXT)
        ->and($message->meta_message_id)->toBe('wamid.TEXT')
        ->and($message->tenant_id)->toBe($this->tenant->id);

    // firstOrCreate ha normalizzato il numero a E.164 senza '+'
    expect($message->contact->phone)->toBe('393384852605');

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $request->hasHeader('Authorization', 'Bearer TENANT-TOKEN')
            && str_contains($request->url(), '/1147055808484414/messages')
            && $body['messaging_product'] === 'whatsapp'
            && $body['to'] === '393384852605'
            && $body['type'] === 'text'
            && $body['text']['body'] === 'Ciao!';
    });
});

it('costruisce il payload template con lingua e componenti', function () {
    fakeAccepted();

    $components = [[
        'type' => 'body',
        'parameters' => [['type' => 'text', 'text' => 'Giovanni']],
    ]];

    $this->service->sendTemplate('393384852605', 'appointment_reminder', 'it', $components);

    Http::assertSent(function (Request $request) use ($components) {
        $body = $request->data();

        return $body['type'] === 'template'
            && $body['template']['name'] === 'appointment_reminder'
            && $body['template']['language']['code'] === 'it'
            && $body['template']['components'] === $components;
    });
});

it('riusa il contatto esistente invece di duplicarlo', function () {
    fakeAccepted();
    $contact = $this->tenant->contacts()->create(['phone' => '393384852605', 'name' => 'Gio']);

    $this->service->sendText($contact, 'a');
    $this->service->sendText('393384852605', 'b');

    expect($this->tenant->contacts()->count())->toBe(1)
        ->and(Message::where('contact_id', $contact->id)->count())->toBe(2);
});

it('rifiuta più di 3 reply button', function () {
    fakeAccepted();

    $this->service->sendButtons('393384852605', 'Scegli', [
        ['id' => 'a', 'title' => 'A'],
        ['id' => 'b', 'title' => 'B'],
        ['id' => 'c', 'title' => 'C'],
        ['id' => 'd', 'title' => 'D'],
    ]);
})->throws(InvalidArgumentException::class);

it('rifiuta una lista con più di 10 righe', function () {
    fakeAccepted();

    $rows = collect(range(1, 11))->map(fn ($i) => ['id' => "r$i", 'title' => "R$i"])->all();

    $this->service->sendList('393384852605', 'Menu', 'Apri', [['rows' => $rows]]);
})->throws(InvalidArgumentException::class);

it('marca il messaggio failed e lancia WhatsAppApiException su errore Meta', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Invalid OAuth access token', 'code' => 190],
        ], 401),
    ]);

    try {
        $this->service->sendText('393384852605', 'Ciao');
        $this->fail('Doveva lanciare WhatsAppApiException');
    } catch (WhatsAppApiException $e) {
        expect($e->metaCode)->toBe(190)
            ->and($e->httpStatus)->toBe(401);
    }

    $message = Message::sole();
    expect($message->status)->toBe(Message::STATUS_FAILED)
        ->and($message->error['code'])->toBe(190);
});

it('ritenta sugli errori transitori (429) e poi va a buon fine', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'rate limited', 'code' => 130429]], 429)
            ->push(['messages' => [['id' => 'wamid.RETRIED']]], 200),
    ]);

    $message = $this->service->sendText('393384852605', 'Ciao');

    expect($message->status)->toBe(Message::STATUS_SENT)
        ->and($message->meta_message_id)->toBe('wamid.RETRIED');

    Http::assertSentCount(2);
});
