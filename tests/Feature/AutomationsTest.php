<?php

use App\Livewire\Automations;
use App\Models\Automation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
