<?php

use App\Models\Appointment;
use App\Models\Automation;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\Automation\ReviewRequest;
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
        'opted_in' => true,
        'opted_in_at' => now(),
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.OUT']],
        ], 200),
    ]);
});

function activateReviewRequest(Tenant $tenant, array $config = []): Automation
{
    return $tenant->automations()->create([
        'type' => Automation::TYPE_REVIEW_REQUEST,
        'trigger' => 'schedule',
        'config' => $config,
        'active' => true,
    ]);
}

it('invia la richiesta recensione dopo il delay per un appuntamento completato', function () {
    activateReviewRequest($this->tenant);
    $now = Carbon::parse('2026-06-12 10:00');
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25), // > 24h fa → dovuto
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    $sent = ReviewRequest::for($this->tenant)->dispatchDue($now);

    expect($sent)->toBe(1)
        ->and($appointment->fresh()->review_requested)->toBeTrue();

    Http::assertSent(function (Request $request) {
        $body = $request->data();
        $params = $body['template']['components'][0]['parameters'] ?? [];

        return ($body['type'] ?? null) === 'template'
            && $body['template']['name'] === 'review_request'
            && $params[0]['text'] === 'Giovanni';
    });
});

it('non invia prima che sia trascorso il delay', function () {
    activateReviewRequest($this->tenant);
    $now = Carbon::parse('2026-06-12 10:00');
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(2), // troppo recente
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    expect(ReviewRequest::for($this->tenant)->dispatchDue($now))->toBe(0)
        ->and($appointment->fresh()->review_requested)->toBeFalse();
    Http::assertNothingSent();
});

it('non invia per appuntamenti non completati', function () {
    activateReviewRequest($this->tenant);
    $now = Carbon::parse('2026-06-12 10:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    expect(ReviewRequest::for($this->tenant)->dispatchDue($now))->toBe(0);
    Http::assertNothingSent();
});

it('è idempotente: non richiede due volte lo stesso appuntamento', function () {
    activateReviewRequest($this->tenant);
    $now = Carbon::parse('2026-06-12 10:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    $review = ReviewRequest::for($this->tenant);
    expect($review->dispatchDue($now))->toBe(1);
    expect($review->dispatchDue($now->copy()->addHour()))->toBe(0);
    Http::assertSentCount(1);
});

it('salta i contatti senza opt-in e consuma comunque l\'appuntamento', function () {
    activateReviewRequest($this->tenant);
    $now = Carbon::parse('2026-06-12 10:00');
    $contact = $this->tenant->contacts()->create([
        'phone' => '393331112233',
        'name' => 'Anna',
        'opted_in' => false,
    ]);
    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    expect(ReviewRequest::for($this->tenant)->dispatchDue($now))->toBe(0)
        ->and($appointment->fresh()->review_requested)->toBeTrue();
    Http::assertNothingSent();
});

it('rispetta il rate limit per-contatto e consuma l\'appuntamento', function () {
    activateReviewRequest($this->tenant, ['rate_limit_days' => 30]);
    $now = Carbon::parse('2026-06-12 10:00');

    // Richiesta recensione già inviata 10 giorni fa allo stesso contatto.
    $this->tenant->messages()->create([
        'contact_id' => $this->contact->id,
        'direction' => Message::DIRECTION_OUTBOUND,
        'type' => Message::TYPE_TEMPLATE,
        'content' => ['template' => 'review_request'],
        'status' => Message::STATUS_SENT,
    ])->forceFill(['created_at' => $now->copy()->subDays(10)])->save();

    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    expect(ReviewRequest::for($this->tenant)->dispatchDue($now))->toBe(0)
        ->and($appointment->fresh()->review_requested)->toBeTrue();
    Http::assertNothingSent();
});

it('invia di nuovo dopo che la finestra di rate limit è scaduta', function () {
    activateReviewRequest($this->tenant, ['rate_limit_days' => 30]);
    $now = Carbon::parse('2026-06-12 10:00');

    $this->tenant->messages()->create([
        'contact_id' => $this->contact->id,
        'direction' => Message::DIRECTION_OUTBOUND,
        'type' => Message::TYPE_TEMPLATE,
        'content' => ['template' => 'review_request'],
        'status' => Message::STATUS_SENT,
    ])->forceFill(['created_at' => $now->copy()->subDays(40)])->save(); // oltre la finestra

    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    expect(ReviewRequest::for($this->tenant)->dispatchDue($now))->toBe(1);
    Http::assertSentCount(1);
});

it('non invia nulla se il flusso recensioni non è attivo', function () {
    $now = Carbon::parse('2026-06-12 10:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    expect(ReviewRequest::for($this->tenant)->dispatchDue($now))->toBe(0);
    Http::assertNothingSent();
});

it('aggiunge il parametro del button URL quando configurato', function () {
    activateReviewRequest($this->tenant, ['review_url_param' => 'studio-rossi']);
    $now = Carbon::parse('2026-06-12 10:00');
    $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    ReviewRequest::for($this->tenant)->dispatchDue($now);

    Http::assertSent(function (Request $request) {
        $components = $request->data()['template']['components'] ?? [];
        $button = collect($components)->firstWhere('type', 'button');

        return $button !== null
            && $button['sub_type'] === 'url'
            && $button['parameters'][0]['text'] === 'studio-rossi';
    });
});

it('il comando schedulato invia le richieste dovute su tutti i tenant attivi', function () {
    activateReviewRequest($this->tenant);
    $this->tenant->update(['reviews_addon' => true]); // gating add-on Recensioni
    $now = Carbon::parse('2026-06-12 10:00');
    Carbon::setTestNow($now);

    $appointment = $this->tenant->appointments()->create([
        'contact_id' => $this->contact->id,
        'scheduled_at' => $now->copy()->subHours(25),
        'status' => Appointment::STATUS_COMPLETED,
    ]);

    $this->artisan('replisa:send-review-requests')->assertSuccessful();

    expect($appointment->fresh()->review_requested)->toBeTrue();
    Http::assertSentCount(1);

    Carbon::setTestNow();
});
