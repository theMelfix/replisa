<?php

use App\Jobs\SendCampaign;
use App\Livewire\Campaigns;
use App\Models\Campaign;
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

    $this->tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PID', 'access_token' => 'TOK', 'active' => true]);
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
