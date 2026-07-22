<?php

use App\Jobs\SendCampaign;
use App\Livewire\Campaigns;
use App\Models\Campaign;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    // manual_plan base → piano che include le campagne (gating per piano).
    $this->tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PID', 'access_token' => 'TOK', 'active' => true, 'manual_plan' => 'base']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/campaigns')->assertRedirect(route('login'));
});

it('mostra la pagina campagne a un owner', function () {
    $this->actingAs($this->owner);

    $this->get('/campaigns')->assertOk()->assertSee('Campagne e comunicazioni');
});

it('avvia una campagna e accoda il job', function () {
    Bus::fake();
    $this->actingAs($this->owner);
    $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => true]);

    Livewire::test(Campaigns::class)
        ->set('name', 'Promo IMU')
        ->set('template_name', 'promo_x')
        ->call('send')
        ->assertDispatched('toast');

    expect(Campaign::withoutGlobalScopes()->count())->toBe(1);
    Bus::assertDispatched(SendCampaign::class);
});

it('non avvia una campagna senza contatti opt-in', function () {
    Bus::fake();
    $this->actingAs($this->owner);
    $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => false]);

    Livewire::test(Campaigns::class)
        ->set('name', 'Promo')
        ->set('template_name', 'promo_x')
        ->call('send')
        ->assertDispatched('toast');

    expect(Campaign::withoutGlobalScopes()->count())->toBe(0);
    Bus::assertNotDispatched(SendCampaign::class);
});

it('un piano senza campagne blocca l\'invio', function () {
    Bus::fake();
    $this->actingAs($this->owner);
    $this->tenant->update(['manual_plan' => 'starter']); // Starter: niente campagne
    $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => true]);

    Livewire::test(Campaigns::class)
        ->set('name', 'X')
        ->set('template_name', 't')
        ->call('send')
        ->assertDispatched('toast');

    expect(Campaign::withoutGlobalScopes()->count())->toBe(0);
    Bus::assertNotDispatched(SendCampaign::class);
});

it('blocca l\'invio oltre il pacchetto messaggi campagna del piano', function () {
    config(['plans.plans.base.limits.campaign_messages' => 1]);
    Bus::fake();
    $this->actingAs($this->owner);
    $this->tenant->contacts()->create(['phone' => '391', 'name' => 'A', 'opted_in' => true]);
    $this->tenant->contacts()->create(['phone' => '392', 'name' => 'B', 'opted_in' => true]);

    Livewire::test(Campaigns::class)
        ->set('name', 'X')
        ->set('template_name', 't')
        ->call('send')
        ->assertDispatched('toast');

    expect(Campaign::withoutGlobalScopes()->count())->toBe(0);
    Bus::assertNotDispatched(SendCampaign::class);
});

it('il job invia il template solo ai contatti opt-in e completa', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200)]);

    $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => true]);
    $this->tenant->contacts()->create(['phone' => '392', 'name' => 'Bea', 'opted_in' => false]);

    $campaign = Campaign::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Promo',
        'template_name' => 'promo_x',
        'language' => 'it',
        'status' => Campaign::STATUS_PENDING,
        'total' => 1,
    ]);

    (new SendCampaign($campaign))->handle();

    $campaign->refresh();
    expect($campaign->status)->toBe(Campaign::STATUS_COMPLETED)
        ->and($campaign->sent_count)->toBe(1)
        ->and($campaign->failed_count)->toBe(0);

    Http::assertSentCount(1);
});

it('segmenta la campagna per etichetta: conteggio e job filtrati', function () {
    Bus::fake();
    $this->actingAs($this->owner);

    $vip = Tag::create(['name' => 'VIP']);
    $c1 = $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => true]);
    $c2 = $this->tenant->contacts()->create(['phone' => '392', 'name' => 'Bea', 'opted_in' => true]);
    $c1->tags()->attach($vip->id); // solo Anna è VIP

    Livewire::test(Campaigns::class)
        ->set('name', 'Promo VIP')
        ->set('template_name', 'promo_vip')
        ->set('tag_id', (string) $vip->id)
        ->assertSee('1') // destinatari del segmento aggiornato live
        ->call('send')
        ->assertDispatched('toast');

    $campaign = Campaign::withoutGlobalScopes()->latest('id')->first();
    expect($campaign->tag_id)->toBe($vip->id)
        ->and($campaign->total)->toBe(1);

    Bus::assertDispatched(SendCampaign::class);
});

it('senza segmento invia a tutti gli opt-in', function () {
    Bus::fake();
    $this->actingAs($this->owner);

    $this->tenant->contacts()->create(['phone' => '391', 'opted_in' => true]);
    $this->tenant->contacts()->create(['phone' => '392', 'opted_in' => true]);
    $this->tenant->contacts()->create(['phone' => '393', 'opted_in' => false]);

    Livewire::test(Campaigns::class)
        ->set('name', 'Tutti')
        ->set('template_name', 'promo')
        ->call('send');

    $campaign = Campaign::withoutGlobalScopes()->latest('id')->first();
    expect($campaign->tag_id)->toBeNull()->and($campaign->total)->toBe(2);
});

it('blocca un segmento vuoto', function () {
    Bus::fake();
    $this->actingAs($this->owner);

    $tag = Tag::create(['name' => 'Vuoto']);
    $this->tenant->contacts()->create(['phone' => '391', 'opted_in' => true]); // senza tag

    Livewire::test(Campaigns::class)
        ->set('name', 'X')->set('template_name', 't')
        ->set('tag_id', (string) $tag->id)
        ->call('send')
        ->assertDispatched('toast');

    expect(Campaign::withoutGlobalScopes()->count())->toBe(0);
    Bus::assertNotDispatched(SendCampaign::class);
});

it('rifiuta un tag di un altro tenant', function () {
    Bus::fake();
    $other = Tenant::create(['name' => 'B', 'phone_number_id' => 'P2', 'access_token' => 'T2']);
    $foreignTag = Tag::withoutGlobalScopes()->create(['tenant_id' => $other->id, 'name' => 'Altrui']);

    $this->actingAs($this->owner);
    $this->tenant->contacts()->create(['phone' => '391', 'opted_in' => true]);

    Livewire::test(Campaigns::class)
        ->set('name', 'X')->set('template_name', 't')
        ->set('tag_id', (string) $foreignTag->id)
        ->call('send')
        ->assertHasErrors('tag_id');

    expect(Campaign::withoutGlobalScopes()->count())->toBe(0);
});

it('il job invia solo ai contatti del segmento', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);
    $this->actingAs($this->owner);

    $vip = Tag::create(['name' => 'VIP']);
    $anna = $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => true]);
    $this->tenant->contacts()->create(['phone' => '392', 'name' => 'Bea', 'opted_in' => true]);
    $anna->tags()->attach($vip->id);

    $campaign = Campaign::create([
        'tag_id' => $vip->id, 'name' => 'Promo', 'template_name' => 'promo',
        'language' => 'it', 'status' => Campaign::STATUS_PENDING, 'total' => 1,
    ]);

    (new SendCampaign($campaign))->handle();

    expect($campaign->fresh()->sent_count)->toBe(1);
    // Un solo invio (solo Anna), non due.
    Http::assertSentCount(1);
});
