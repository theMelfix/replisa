<?php

use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\Appointment;
use App\Models\Automation;
use App\Models\Tenant;
use App\Services\Automation\AppointmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.meta.graph_version' => 'v25.0']);

    $this->tenant = Tenant::create([
        'name' => 'Studio Dentistico Rossi',
        'phone_number_id' => 'PNID-1',
        'waba_id' => 'WABA-1',
        'access_token' => 'TENANT-TOKEN',
    ]);

    $this->contact = $this->tenant->contacts()->create([
        'phone' => '393334445566',
        'name' => 'Giovanni',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.OUT']],
        ], 200),
    ]);
});

function activateReminder(Tenant $tenant, array $config = []): Automation
{
    return $tenant->automations()->create([
        'type' => Automation::TYPE_APPOINTMENT_REMINDER,
        'trigger' => 'schedule',
        'config' => $config,
        'active' => true,
    ]);
}

it('invia il reminder nella finestra -24h con i parametri del template', function () {
    activateReminder($this->tenant);
    $now = Carbon::parse('2026-06-10 09:00');
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->addHours(20),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    $sent = AppointmentReminder::for($this->tenant)->dispatchDue($now);

    expect($sent)->toBe(1)
        ->and($appointment->fresh()->reminded_at)->not->toBeNull();

    Http::assertSent(function (Request $request) {
        $body = $request->data();
        $params = $body['template']['components'][0]['parameters'] ?? [];

        return ($body['type'] ?? null) === 'template'
            && $body['template']['name'] === 'appointment_reminder'
            && $params[0]['text'] === 'Giovanni'
            && $params[1]['text'] === '11/06/2026'   // +20h da 2026-06-10 09:00 → 11/06 05:00
            && $params[2]['text'] === '05:00';
    });
});

it('non reinvia lo stesso reminder nella medesima finestra (idempotente)', function () {
    activateReminder($this->tenant);
    $now = Carbon::parse('2026-06-10 09:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->addHours(20),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    $reminder = AppointmentReminder::for($this->tenant);
    expect($reminder->dispatchDue($now))->toBe(1);
    expect($reminder->dispatchDue($now->copy()->addHour()))->toBe(0);

    Http::assertSentCount(1);
});

it('invia il secondo reminder nella finestra -2h dopo quello a -24h', function () {
    activateReminder($this->tenant);
    $now = Carbon::parse('2026-06-10 09:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->addHour(),         // a 1h → finestra -2h
        'reminded_at' => $now->copy()->subHours(20),        // reminder -24h già inviato
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    expect(AppointmentReminder::for($this->tenant)->dispatchDue($now))->toBe(1);
    Http::assertSentCount(1);
});

it('non invia reminder per appuntamenti non più scheduled', function () {
    activateReminder($this->tenant);
    $now = Carbon::parse('2026-06-10 09:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->addHours(20),
        'status' => Appointment::STATUS_CANCELLED,
    ]);

    expect(AppointmentReminder::for($this->tenant)->dispatchDue($now))->toBe(0);
    Http::assertNothingSent();
});

it('non invia nulla se il flusso reminder non è attivo', function () {
    $now = Carbon::parse('2026-06-10 09:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->addHours(20),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    expect(AppointmentReminder::for($this->tenant)->dispatchDue($now))->toBe(0);
    Http::assertNothingSent();
});

it('conferma l\'appuntamento alla risposta "Confermo"', function () {
    activateReminder($this->tenant);
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => now()->addHours(2),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    $result = AppointmentReminder::for($this->tenant)->handleButtonReply($this->contact, 'CONFIRM');

    expect($result?->id)->toBe($appointment->id)
        ->and($appointment->fresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('disdice l\'appuntamento alla risposta "Disdici"', function () {
    activateReminder($this->tenant);
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => now()->addHours(2),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    AppointmentReminder::for($this->tenant)->handleButtonReply($this->contact, 'CANCEL');

    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_CANCELLED);
});

it('ignora payload sconosciuti senza toccare lo stato', function () {
    activateReminder($this->tenant);
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => now()->addHours(2),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    $result = AppointmentReminder::for($this->tenant)->handleButtonReply($this->contact, 'BOH');

    expect($result)->toBeNull()
        ->and($appointment->fresh()->status)->toBe(Appointment::STATUS_SCHEDULED);
});

it('instrada il quick-reply del template via webhook (Conferma)', function () {
    activateReminder($this->tenant);
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => now()->addHours(2),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    (new ProcessWhatsAppWebhook([
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA-1',
            'changes' => [['field' => 'messages', 'value' => [
                'metadata' => ['phone_number_id' => 'PNID-1'],
                'messages' => [[
                    'from' => '393334445566',
                    'id' => 'wamid.BTN',
                    'type' => 'button',
                    'button' => ['payload' => 'CONFIRM', 'text' => 'Confermo'],
                ]],
            ]]],
        ]],
    ]))->handle();

    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('il comando schedulato invia i reminder dovuti su tutti i tenant attivi', function () {
    activateReminder($this->tenant);
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => now()->addHours(20),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    $this->artisan('replisa:send-reminders')->assertSuccessful();

    expect($appointment->fresh()->reminded_at)->not->toBeNull();
    Http::assertSentCount(1);
});
