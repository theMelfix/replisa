<?php

use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Sandbox',
        'phone_number_id' => 'PNID-1',
        'waba_id' => 'WABA-1',
        'access_token' => 'tok',
    ]);
});

function messagesEvent(array $value): array
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

it('registra un messaggio di testo in ingresso e crea il contatto', function () {
    (new ProcessWhatsAppWebhook(messagesEvent([
        'contacts' => [['wa_id' => '393384852605', 'profile' => ['name' => 'Giovanni']]],
        'messages' => [[
            'from' => '393384852605',
            'id' => 'wamid.IN1',
            'type' => 'text',
            'text' => ['body' => 'Ciao!'],
        ]],
    ])))->handle();

    $contact = $this->tenant->contacts()->sole();
    expect($contact->phone)->toBe('393384852605')
        ->and($contact->name)->toBe('Giovanni')
        ->and($contact->last_seen_at)->not->toBeNull();

    $message = Message::sole();
    expect($message->direction)->toBe(Message::DIRECTION_INBOUND)
        ->and($message->status)->toBe(Message::STATUS_RECEIVED)
        ->and($message->type)->toBe('text')
        ->and($message->meta_message_id)->toBe('wamid.IN1')
        ->and($message->content['body'])->toBe('Ciao!');
});

it('parsa la risposta a un bottone interattivo', function () {
    (new ProcessWhatsAppWebhook(messagesEvent([
        'messages' => [[
            'from' => '393384852605',
            'id' => 'wamid.IN2',
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button_reply',
                'button_reply' => ['id' => 'prenota', 'title' => 'Prenota'],
            ],
        ]],
    ])))->handle();

    $content = Message::sole()->content;
    expect($content['reply_type'])->toBe('button_reply')
        ->and($content['id'])->toBe('prenota')
        ->and($content['title'])->toBe('Prenota');
});

it('aggiorna lo stato di un messaggio inviato', function () {
    $contact = $this->tenant->contacts()->create(['phone' => '393384852605']);
    $message = $this->tenant->messages()->create([
        'contact_id' => $contact->id,
        'direction' => Message::DIRECTION_OUTBOUND,
        'type' => Message::TYPE_TEMPLATE,
        'status' => Message::STATUS_SENT,
        'meta_message_id' => 'wamid.OUT1',
    ]);

    (new ProcessWhatsAppWebhook(messagesEvent([
        'statuses' => [['id' => 'wamid.OUT1', 'status' => 'delivered', 'recipient_id' => '393384852605']],
    ])))->handle();

    expect($message->fresh()->status)->toBe(Message::STATUS_DELIVERED);
});

it('salva il dettaglio errore su uno status failed', function () {
    $contact = $this->tenant->contacts()->create(['phone' => '393384852605']);
    $message = $this->tenant->messages()->create([
        'contact_id' => $contact->id,
        'direction' => Message::DIRECTION_OUTBOUND,
        'type' => Message::TYPE_TEXT,
        'status' => Message::STATUS_SENT,
        'meta_message_id' => 'wamid.OUT2',
    ]);

    (new ProcessWhatsAppWebhook(messagesEvent([
        'statuses' => [[
            'id' => 'wamid.OUT2',
            'status' => 'failed',
            'errors' => [['code' => 131026, 'title' => 'Message undeliverable']],
        ]],
    ])))->handle();

    $message->refresh();
    expect($message->status)->toBe(Message::STATUS_FAILED)
        ->and($message->error[0]['code'])->toBe(131026);
});

it('ignora eventi per un phone_number_id sconosciuto', function () {
    (new ProcessWhatsAppWebhook([
        'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'metadata' => ['phone_number_id' => 'ALTRO'],
            'messages' => [['from' => '39000', 'id' => 'wamid.X', 'type' => 'text', 'text' => ['body' => 'x']]],
        ]]]]],
    ]))->handle();

    expect(Message::count())->toBe(0);
});

it('è idempotente sui messaggi riconsegnati', function () {
    $payload = messagesEvent([
        'messages' => [[
            'from' => '393384852605',
            'id' => 'wamid.DUP',
            'type' => 'text',
            'text' => ['body' => 'una volta sola'],
        ]],
    ]);

    (new ProcessWhatsAppWebhook($payload))->handle();
    (new ProcessWhatsAppWebhook($payload))->handle();

    expect(Message::where('meta_message_id', 'wamid.DUP')->count())->toBe(1);
});
