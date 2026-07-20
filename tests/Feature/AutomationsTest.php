<?php

use App\Livewire\Automations;
use App\Models\Automation;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Automation\WelcomeFlow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'Studio A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/automations')->assertRedirect(route('login'));
});

it('mostra i tre flussi a un owner', function () {
    $this->actingAs($this->owner);

    $this->get('/automations')
        ->assertOk()
        ->assertSee('Benvenuto automatico')
        ->assertSee('Promemoria')
        ->assertSee('Campagne e comunicazioni');
});

it('attiva un flusso creando l\'automation per il tenant', function () {
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)->call('toggle', Automation::TYPE_WELCOME);

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_WELCOME)->first();

    expect($automation)->not->toBeNull()
        ->and($automation->active)->toBeTrue()
        ->and($automation->tenant_id)->toBe($this->tenant->id);
});

it('disattiva un flusso già attivo al secondo toggle', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(Automations::class);
    $component->call('toggle', Automation::TYPE_APPOINTMENT_REMINDER);
    $component->call('toggle', Automation::TYPE_APPOINTMENT_REMINDER);

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_APPOINTMENT_REMINDER)->first();

    expect($automation->active)->toBeFalse();
});

it('mostra un toast e non crea nulla per un utente senza tenant (super-admin)', function () {
    $admin = User::create([
        'tenant_id' => null,
        'name' => 'Super',
        'email' => 'super@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    $this->actingAs($admin);

    Livewire::test(Automations::class)
        ->call('toggle', Automation::TYPE_WELCOME)
        ->assertDispatched('toast');

    expect(Automation::withoutGlobalScopes()->count())->toBe(0);
});

it('blocca l\'attivazione oltre il limite di automazioni del piano', function () {
    // Tenant senza abbonamento → piano default Starter (1 automazione attiva).
    $this->actingAs($this->owner);

    $component = Livewire::test(Automations::class);
    $component->call('toggle', Automation::TYPE_WELCOME);
    $component->call('toggle', Automation::TYPE_APPOINTMENT_REMINDER)
        ->assertDispatched('toast');

    expect(Automation::withoutGlobalScopes()->where('active', true)->count())->toBe(1);
});

it('il toggle recensioni è bloccato senza add-on', function () {
    $this->actingAs($this->owner); // tenant default starter, nessun add-on

    Livewire::test(Automations::class)->call('toggleReviews')->assertDispatched('toast');

    expect(Automation::withoutGlobalScopes()->where('type', Automation::TYPE_REVIEW_REQUEST)->count())->toBe(0);
});

it('il toggle recensioni funziona con l\'add-on attivo', function () {
    $this->tenant->update(['reviews_addon' => true]);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)->call('toggleReviews');

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_REVIEW_REQUEST)->first();
    expect($automation)->not->toBeNull()->and($automation->active)->toBeTrue();
});

it('ignora tipi di flusso sconosciuti', function () {
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)->call('toggle', 'inesistente');

    expect(Automation::withoutGlobalScopes()->count())->toBe(0);
});

/** Helper: crea l'Automation recensione attiva per il tenant del test. */
function activateReviewAutomation($tenant, array $config = []): Automation
{
    return Automation::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'type' => Automation::TYPE_REVIEW_REQUEST,
        'trigger' => 'schedule',
        'config' => $config,
        'active' => true,
    ]);
}

it('salva il link recensione dinamico nella config dell\'automazione', function () {
    $this->tenant->update(['reviews_addon' => true]);
    activateReviewAutomation($this->tenant);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reviewUrlMode', 'dynamic')
        ->set('reviewUrlParam', 'ChIJabc123')
        ->call('saveReviewSettings')
        ->assertDispatched('toast');

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_REVIEW_REQUEST)->first();
    expect($automation->config['review_url_param'])->toBe('ChIJabc123');
});

it('in modalità statico azzera il param anche se era valorizzato', function () {
    $this->tenant->update(['reviews_addon' => true]);
    activateReviewAutomation($this->tenant, ['review_url_param' => 'ChIJabc123']);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reviewUrlMode', 'static')
        ->call('saveReviewSettings');

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_REVIEW_REQUEST)->first();
    expect($automation->config['review_url_param'])->toBeNull();
});

it('rifiuta la modalità dinamica con suffisso vuoto', function () {
    $this->tenant->update(['reviews_addon' => true]);
    activateReviewAutomation($this->tenant);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reviewUrlMode', 'dynamic')
        ->set('reviewUrlParam', '')
        ->call('saveReviewSettings')
        ->assertHasErrors('reviewUrlParam');

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_REVIEW_REQUEST)->first();
    expect($automation->config['review_url_param'] ?? null)->toBeNull();
});

it('non salva il link recensione senza add-on', function () {
    activateReviewAutomation($this->tenant); // automazione esiste ma niente add-on
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reviewUrlMode', 'dynamic')
        ->set('reviewUrlParam', 'ChIJabc123')
        ->call('saveReviewSettings')
        ->assertDispatched('toast');

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_REVIEW_REQUEST)->first();
    expect($automation->config['review_url_param'] ?? null)->toBeNull();
});

it('inferisce la modalità dinamica al mount se il param è già configurato', function () {
    $this->tenant->update(['reviews_addon' => true]);
    activateReviewAutomation($this->tenant, ['review_url_param' => 'ChIJabc123']);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->assertSet('reviewUrlMode', 'dynamic')
        ->assertSet('reviewUrlParam', 'ChIJabc123');
});

it('preserva le altre chiavi di config al salvataggio del link', function () {
    $this->tenant->update(['reviews_addon' => true]);
    activateReviewAutomation($this->tenant, ['delay_hours' => 48]);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reviewUrlMode', 'dynamic')
        ->set('reviewUrlParam', 'ChIJabc123')
        ->call('saveReviewSettings');

    $automation = Automation::withoutGlobalScopes()->where('type', Automation::TYPE_REVIEW_REQUEST)->first();
    expect($automation->config['delay_hours'])->toBe(48)
        ->and($automation->config['review_url_param'])->toBe('ChIJabc123');
});

/*
|--------------------------------------------------------------------------
| Configurazione flussi Benvenuto e Promemoria (E4.2.3)
|--------------------------------------------------------------------------
*/

/** Helper: crea un'Automation attiva del tipo dato per il tenant del test. */
function activateAutomation($tenant, string $type, array $config = []): Automation
{
    return Automation::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'type' => $type,
        'trigger' => Automations::FLOWS[$type]['trigger'],
        'config' => $config,
        'active' => true,
    ]);
}

/** Helper: la config salvata sull'Automation del tipo dato. */
function configOf(string $type): array
{
    return Automation::withoutGlobalScopes()->where('type', $type)->first()->config ?? [];
}

it('precompila il form benvenuto con i default del flusso', function () {
    activateAutomation($this->tenant, Automation::TYPE_WELCOME);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->assertSet('welcomeGreeting', WelcomeFlow::DEFAULT_GREETING)
        ->assertSet('welcomeButtons.0.title', 'Info Servizi')
        ->assertSet('welcomeButtons.0.id', 'info_servizi');
});

it('salva testi e bottoni del menu di benvenuto', function () {
    activateAutomation($this->tenant, Automation::TYPE_WELCOME);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('welcomeGreeting', 'Benvenuto in Studio A!')
        ->set('welcomeHeader', 'Studio A')
        ->set('welcomeFooter', 'Lun-Ven 9-18')
        ->set('welcomeButtons.0.title', 'Orari')
        ->set('welcomeButtons.0.reply', 'Siamo aperti dalle 9 alle 18.')
        ->call('saveWelcomeSettings')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $config = configOf(Automation::TYPE_WELCOME);

    expect($config['greeting'])->toBe('Benvenuto in Studio A!')
        ->and($config['header'])->toBe('Studio A')
        ->and($config['footer'])->toBe('Lun-Ven 9-18')
        ->and($config['buttons'][0])->toBe(['id' => 'info_servizi', 'title' => 'Orari'])
        ->and($config['replies']['info_servizi'])->toBe('Siamo aperti dalle 9 alle 18.');
});

it('scarta gli slot bottone lasciati senza titolo', function () {
    activateAutomation($this->tenant, Automation::TYPE_WELCOME);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('welcomeButtons.1.title', '')
        ->set('welcomeButtons.2.title', '')
        ->call('saveWelcomeSettings');

    expect(configOf(Automation::TYPE_WELCOME)['buttons'])->toHaveCount(1);
});

it('rifiuta un menu di benvenuto senza nessun bottone', function () {
    activateAutomation($this->tenant, Automation::TYPE_WELCOME);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('welcomeButtons.0.title', '')
        ->set('welcomeButtons.1.title', '')
        ->set('welcomeButtons.2.title', '')
        ->call('saveWelcomeSettings')
        ->assertHasErrors('welcomeButtons.0.title');

    expect(configOf(Automation::TYPE_WELCOME))->toBe([]);
});

it('non salva la configurazione se il flusso non è ancora attivo', function () {
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->call('saveWelcomeSettings')
        ->assertDispatched('toast');

    expect(Automation::withoutGlobalScopes()->count())->toBe(0);
});

it('salva i parametri dei promemoria ordinando gli anticipi', function () {
    activateAutomation($this->tenant, Automation::TYPE_APPOINTMENT_REMINDER);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reminderTemplate', 'promemoria_studio')
        ->set('reminderLanguage', 'it')
        ->set('reminderOffsets', '2, 48, 24, 24')
        ->set('reminderConfirm', 'Ci sarò')
        ->set('reminderCancel', 'Disdico')
        ->call('saveReminderSettings')
        ->assertHasNoErrors()
        ->assertSet('reminderOffsets', '48, 24, 2');

    $config = configOf(Automation::TYPE_APPOINTMENT_REMINDER);

    expect($config['template'])->toBe('promemoria_studio')
        ->and($config['offsets'])->toBe([48, 24, 2])
        ->and($config['confirm_payload'])->toBe('Ci sarò');
});

it('rifiuta anticipi non numerici o fuori scala', function (string $offsets) {
    activateAutomation($this->tenant, Automation::TYPE_APPOINTMENT_REMINDER);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reminderOffsets', $offsets)
        ->call('saveReminderSettings')
        ->assertHasErrors('reminderOffsets');

    expect(configOf(Automation::TYPE_APPOINTMENT_REMINDER))->toBe([]);
})->with(['24, domani', '0', '1000', '-2']);

it('rifiuta un nome template non conforme a Meta', function () {
    activateAutomation($this->tenant, Automation::TYPE_APPOINTMENT_REMINDER);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reminderTemplate', 'Promemoria Studio')
        ->call('saveReminderSettings')
        ->assertHasErrors('reminderTemplate');
});

it('preserva le altre chiavi di config al salvataggio dei promemoria', function () {
    activateAutomation($this->tenant, Automation::TYPE_APPOINTMENT_REMINDER, ['custom' => 'x']);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('reminderOffsets', '12')
        ->call('saveReminderSettings');

    expect(configOf(Automation::TYPE_APPOINTMENT_REMINDER))
        ->toMatchArray(['custom' => 'x', 'offsets' => [12]]);
});

it('mostra i pannelli di configurazione solo per i flussi attivi', function () {
    $this->actingAs($this->owner);

    $this->get('/automations')
        ->assertOk()
        ->assertDontSee('Messaggio di benvenuto')
        ->assertDontSee('Template Meta');

    activateAutomation($this->tenant, Automation::TYPE_WELCOME);
    activateAutomation($this->tenant, Automation::TYPE_APPOINTMENT_REMINDER);
    activateAutomation($this->tenant, Automation::TYPE_CAMPAIGN);

    $this->get('/automations')
        ->assertOk()
        ->assertSee('Messaggio di benvenuto')
        ->assertSee('Bottoni del menu')
        ->assertSee('Template Meta')
        ->assertSee('Quanto tempo prima')
        ->assertSee(route('campaigns'));
});

it('il menu salvato dalla UI è quello che parte davvero su WhatsApp', function () {
    Http::fake(['graph.facebook.com/*' => Http::response([
        'messaging_product' => 'whatsapp',
        'messages' => [['id' => 'wamid.OUT']],
    ], 200)]);

    activateAutomation($this->tenant, Automation::TYPE_WELCOME);
    $this->actingAs($this->owner);

    Livewire::test(Automations::class)
        ->set('welcomeGreeting', 'Ciao dallo Studio A')
        ->set('welcomeHeader', 'Studio A')
        ->set('welcomeButtons.0.title', 'Orari')
        ->set('welcomeButtons.0.reply', 'Apriamo alle 9.')
        ->set('welcomeButtons.1.title', '')
        ->set('welcomeButtons.2.title', '')
        ->call('saveWelcomeSettings');

    $contact = Contact::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'phone' => '393334445556',
        'name' => 'Cliente',
    ]);

    WelcomeFlow::for($this->tenant->fresh())->greet($contact);

    Http::assertSent(function ($request) {
        $interactive = $request->data()['interactive'];

        return $interactive['body']['text'] === 'Ciao dallo Studio A'
            && $interactive['header']['text'] === 'Studio A'
            && $interactive['action']['buttons'] === [[
                'type' => 'reply',
                'reply' => ['id' => 'welcome:info_servizi', 'title' => 'Orari'],
            ]];
    });

    // Il tocco sul bottone deve trovare la risposta salvata sotto lo stesso id.
    $reply = WelcomeFlow::for($this->tenant->fresh())->handleButtonReply($contact, 'welcome:info_servizi');

    expect($reply)->not->toBeNull()
        ->and($reply->content['body'])->toBe('Apriamo alle 9.');
});
