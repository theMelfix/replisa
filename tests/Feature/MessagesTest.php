<?php

use App\Livewire\Messages;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->other = Tenant::create(['name' => 'B', 'phone_number_id' => 'PB', 'access_token' => 'TB']);

    $ca = $this->tenant->contacts()->create(['phone' => '393331110001', 'name' => 'Anna']);
    $cb = $this->other->contacts()->create(['phone' => '393339990003', 'name' => 'Estraneo']);

    $this->tenant->messages()->create(['contact_id' => $ca->id, 'direction' => Message::DIRECTION_OUTBOUND, 'type' => 'text', 'content' => ['body' => 'Ciao Anna'], 'status' => 'sent']);
    $this->tenant->messages()->create(['contact_id' => $ca->id, 'direction' => Message::DIRECTION_INBOUND, 'type' => 'text', 'content' => ['body' => 'Risposta Anna'], 'status' => 'received']);
    $this->other->messages()->create(['contact_id' => $cb->id, 'direction' => Message::DIRECTION_OUTBOUND, 'type' => 'text', 'content' => ['body' => 'Segreto altrui'], 'status' => 'sent']);

    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/messages')->assertRedirect(route('login'));
});

it('mostra solo i messaggi del proprio tenant', function () {
    $this->actingAs($this->owner);

    Livewire::test(Messages::class)
        ->assertSee('Ciao Anna')
        ->assertSee('Risposta Anna')
        ->assertDontSee('Segreto altrui');
});

it('filtra per direzione', function () {
    $this->actingAs($this->owner);

    Livewire::test(Messages::class)
        ->set('direction', Message::DIRECTION_INBOUND)
        ->assertSee('Risposta Anna')
        ->assertDontSee('Ciao Anna');
});

it('filtra per tipo', function () {
    $ca = $this->tenant->contacts()->first();
    $this->tenant->messages()->create(['contact_id' => $ca->id, 'direction' => Message::DIRECTION_OUTBOUND, 'type' => 'template', 'content' => ['template' => 'appointment_reminder'], 'status' => 'sent']);

    $this->actingAs($this->owner);

    Livewire::test(Messages::class)
        ->set('type', Message::TYPE_TEMPLATE)
        ->assertSee('appointment_reminder')
        ->assertDontSee('Ciao Anna');
});

it('filtra per intervallo di date (estremi inclusivi)', function () {
    $ca = $this->tenant->contacts()->first();

    $vecchio = $this->tenant->messages()->create(['contact_id' => $ca->id, 'direction' => Message::DIRECTION_OUTBOUND, 'type' => 'text', 'content' => ['body' => 'Messaggio vecchio'], 'status' => 'sent']);
    $vecchio->forceFill(['created_at' => now()->subDays(10)])->save();

    $this->actingAs($this->owner);

    // Solo gli ultimi 3 giorni: i due del setup (oggi) restano, il vecchio no.
    Livewire::test(Messages::class)
        ->set('from', now()->subDays(3)->format('Y-m-d'))
        ->assertSee('Ciao Anna')
        ->assertDontSee('Messaggio vecchio');

    // Fino a 5 giorni fa: resta solo il vecchio.
    Livewire::test(Messages::class)
        ->set('to', now()->subDays(5)->format('Y-m-d'))
        ->assertSee('Messaggio vecchio')
        ->assertDontSee('Ciao Anna');
});

it('include i messaggi creati oggi quando "al" è oggi (fine giornata)', function () {
    $this->actingAs($this->owner);

    // `to` = oggi: senza endOfDay i messaggi di oggi (ore > 00:00) sarebbero esclusi.
    Livewire::test(Messages::class)
        ->set('to', now()->format('Y-m-d'))
        ->assertSee('Ciao Anna');
});

it('ignora una data non valida dall\'url senza errori', function () {
    $this->actingAs($this->owner);

    Livewire::test(Messages::class)
        ->set('from', 'non-una-data')
        ->assertSee('Ciao Anna'); // filtro ignorato → mostra tutto
});

it('azzera tutti i filtri', function () {
    $this->actingAs($this->owner);

    Livewire::test(Messages::class)
        ->set('direction', Message::DIRECTION_INBOUND)
        ->set('type', Message::TYPE_TEXT)
        ->set('from', now()->format('Y-m-d'))
        ->call('resetFilters')
        ->assertSet('direction', '')
        ->assertSet('type', '')
        ->assertSet('from', '')
        ->assertSee('Ciao Anna')
        ->assertSee('Risposta Anna');
});
