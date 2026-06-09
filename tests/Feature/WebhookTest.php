<?php

use App\Jobs\ProcessWhatsAppWebhook;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'services.meta.webhook_verify_token' => 'verify-secret',
        'services.meta.app_secret' => 'app-secret',
    ]);
});

function signedPost(array $payload): TestResponse
{
    $raw = json_encode($payload);
    $signature = 'sha256='.hash_hmac('sha256', $raw, 'app-secret');

    // NB: in test gli header custom vanno passati come server var; withHeaders()
    // non li propaga al middleware quando si usa call() con body raw.
    return test()->call('POST', '/webhook', [], [], [], [
        'HTTP_X-Hub-Signature-256' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

it('risponde al challenge di verifica con il token corretto', function () {
    $this->get('/webhook?hub_mode=subscribe&hub_verify_token=verify-secret&hub_challenge=12345')
        ->assertOk()
        ->assertSee('12345');
});

it('rifiuta la verifica con token errato', function () {
    $this->get('/webhook?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')
        ->assertForbidden();
});

it('rifiuta il POST senza firma valida', function () {
    $this->postJson('/webhook', ['entry' => []])->assertForbidden();
});

it('rifiuta il POST con firma non corrispondente', function () {
    test()->call('POST', '/webhook', [], [], [], [
        'HTTP_X-Hub-Signature-256' => 'sha256=deadbeef',
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(['entry' => []]))
        ->assertForbidden();
});

it('accetta il POST firmato e mette in coda il processing', function () {
    Queue::fake();

    signedPost(['object' => 'whatsapp_business_account', 'entry' => []])
        ->assertOk();

    Queue::assertPushed(ProcessWhatsAppWebhook::class, function ($job) {
        return $job->payload['object'] === 'whatsapp_business_account';
    });
});
