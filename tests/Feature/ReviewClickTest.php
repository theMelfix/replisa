<?php

use App\Models\Appointment;
use App\Models\Automation;
use App\Models\ReviewClick;
use App\Models\Tenant;
use App\Services\Automation\ReviewRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Studio A',
        'phone_number_id' => 'PNID-1',
        'waba_id' => 'WABA-1',
        'access_token' => 'TOK',
    ]);

    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);
});

/** Crea l'automazione recensione attiva con la config data. */
function reviewAutomation(Tenant $tenant, array $config): Automation
{
    return $tenant->automations()->create([
        'type' => Automation::TYPE_REVIEW_REQUEST,
        'trigger' => 'schedule',
        'config' => $config,
        'active' => true,
    ]);
}

/** Appuntamento completato pronto per la richiesta, con contatto opt-in. */
function completedAppointment(Tenant $tenant): Appointment
{
    $contact = $tenant->contacts()->create(['phone' => '393331112223', 'name' => 'Mario', 'opted_in' => true]);

    return $tenant->appointments()->create([
        'contact_id' => $contact->id,
        'scheduled_at' => now()->subDays(2),
        'status' => Appointment::STATUS_COMPLETED,
        'review_requested' => false,
    ]);
}

it('in modalità tracciata crea uno short-link e passa il token al template', function () {
    reviewAutomation($this->tenant, ['review_destination_url' => 'https://g.page/r/ABC/review']);
    $appointment = completedAppointment($this->tenant);

    ReviewRequest::for($this->tenant)->request($appointment);

    $link = ReviewClick::withoutGlobalScopes()->sole();
    expect($link->destination_url)->toBe('https://g.page/r/ABC/review')
        ->and($link->appointment_id)->toBe($appointment->id)
        ->and($link->contact_id)->toBe($appointment->contact_id)
        ->and($link->clicks)->toBe(0);

    // Il token dello short-link è passato come parametro del button URL.
    Http::assertSent(function ($request) use ($link) {
        $button = collect($request['template']['components'])->firstWhere('type', 'button');

        return $button && $button['parameters'][0]['text'] === $link->token;
    });
});

it('la rotta /r/{token} registra il click e reindirizza alla destinazione', function () {
    $link = $this->tenant->reviewClicks()->create([
        'token' => 'abc123token',
        'destination_url' => 'https://g.page/r/XYZ/review',
    ]);

    $this->get('/r/abc123token')
        ->assertRedirect('https://g.page/r/XYZ/review');

    $link->refresh();
    expect($link->clicks)->toBe(1)
        ->and($link->first_clicked_at)->not->toBeNull()
        ->and($link->last_clicked_at)->not->toBeNull();
});

it('conta i click multipli sullo stesso link', function () {
    $link = $this->tenant->reviewClicks()->create([
        'token' => 'multi', 'destination_url' => 'https://g.page/r/XYZ/review',
    ]);

    $this->get('/r/multi');
    $this->get('/r/multi');
    $this->get('/r/multi');

    expect($link->refresh()->clicks)->toBe(3);
});

it('un token inesistente dà 404', function () {
    $this->get('/r/non-esiste')->assertNotFound();
});

it('la modalità dinamica non crea short-link', function () {
    reviewAutomation($this->tenant, ['review_url_param' => 'place_id_google']);
    $appointment = completedAppointment($this->tenant);

    ReviewRequest::for($this->tenant)->request($appointment);

    expect(ReviewClick::withoutGlobalScopes()->count())->toBe(0);

    Http::assertSent(function ($request) {
        $button = collect($request['template']['components'])->firstWhere('type', 'button');

        return $button && $button['parameters'][0]['text'] === 'place_id_google';
    });
});
