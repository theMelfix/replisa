<?php

use App\Livewire\ContactForm;
use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('mostra il form nella landing', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeLivewire(ContactForm::class)
        ->assertSee('Prenota una demo');
});

it('non usa più il CTA mailto', function () {
    $this->get('/')->assertDontSee('mailto:info@giovannimelfi.it');
});

it('salva un lead e notifica il team', function () {
    Notification::fake();

    Livewire::test(ContactForm::class)
        ->set('name', 'Mario Rossi')
        ->set('email', 'mario@studio.it')
        ->set('phone', '3331112223')
        ->set('business', 'Studio dentistico')
        ->set('message', 'Vorrei automatizzare i promemoria')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $lead = Lead::sole();
    expect($lead->name)->toBe('Mario Rossi')
        ->and($lead->email)->toBe('mario@studio.it')
        ->and($lead->status)->toBe(Lead::STATUS_NEW);

    Notification::assertSentOnDemand(
        NewLeadNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === config('services.contact.notify_email')
    );
});

it('valida nome ed email', function () {
    Notification::fake();

    Livewire::test(ContactForm::class)
        ->set('name', '')
        ->set('email', 'non-una-email')
        ->call('submit')
        ->assertHasErrors(['name' => 'required', 'email' => 'email']);

    expect(Lead::count())->toBe(0);
    Notification::assertNothingSent();
});

it('l\'honeypot blocca i bot senza salvare né notificare', function () {
    Notification::fake();

    Livewire::test(ContactForm::class)
        ->set('name', 'Bot')
        ->set('email', 'bot@spam.it')
        ->set('website', 'http://spam.it') // campo honeypot compilato
        ->call('submit')
        ->assertSet('sent', true);

    expect(Lead::count())->toBe(0);
    Notification::assertNothingSent();
});

it('resetta i campi dopo l\'invio', function () {
    Notification::fake();

    Livewire::test(ContactForm::class)
        ->set('name', 'Anna')
        ->set('email', 'anna@x.it')
        ->call('submit')
        ->assertSet('name', '')
        ->assertSet('email', '');
});
