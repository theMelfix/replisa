<?php

use App\Jobs\SendCampaign;
use App\Livewire\Deadlines;
use App\Models\Campaign;
use App\Models\Deadline;
use App\Models\DeadlineReminder;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    // manual_plan base → piano che include i promemoria scadenze (gating per piano).
    $this->tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PID', 'access_token' => 'TOK', 'active' => true, 'manual_plan' => 'base']);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);

    $this->national = Deadline::create(['tenant_id' => null, 'name' => 'IMU acconto', 'due_date' => now()->addDays(30), 'active' => true]);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/scadenze')->assertRedirect(route('login'));
});

it('mostra le scadenze nazionali al tenant', function () {
    $this->actingAs($this->owner);

    $this->get('/scadenze')->assertOk()->assertSee('IMU acconto');
});

it('il tenant aggiunge una propria scadenza', function () {
    $this->actingAs($this->owner);

    Livewire::test(Deadlines::class)
        ->set('newName', 'Rinnovo polizza')
        ->set('newDate', now()->addDays(20)->format('Y-m-d'))
        ->call('addDeadline')
        ->assertDispatched('toast');

    expect(Deadline::where('tenant_id', $this->tenant->id)->where('name', 'Rinnovo polizza')->exists())->toBeTrue();
});

it('il tenant attiva un promemoria su una scadenza nazionale', function () {
    $this->actingAs($this->owner);

    Livewire::test(Deadlines::class)
        ->set("reminderTemplate.{$this->national->id}", 'promemoria_imu')
        ->set("reminderDays.{$this->national->id}", 5)
        ->call('saveReminder', $this->national->id)
        ->assertDispatched('toast');

    $reminder = DeadlineReminder::withoutGlobalScopes()->where('deadline_id', $this->national->id)->first();
    expect($reminder)->not->toBeNull()
        ->and($reminder->tenant_id)->toBe($this->tenant->id)
        ->and($reminder->days_before)->toBe(5)
        ->and($reminder->active)->toBeTrue();
});

it('le scadenze nazionali sono filtrate per settore', function () {
    Deadline::create(['tenant_id' => null, 'sector' => 'commercialista', 'name' => '730 scadenza', 'due_date' => now()->addDays(40), 'active' => true]);

    // Commercialista: vede la scadenza fiscale.
    $this->tenant->update(['sector' => 'commercialista']);
    $this->actingAs($this->owner);
    $this->get('/scadenze')->assertOk()->assertSee('730 scadenza');

    // Dentista: NON la vede (ma vede comunque le universali, come IMU acconto del beforeEach).
    $dentista = Tenant::create(['name' => 'Dentista', 'sector' => 'studio_medico', 'active' => true]);
    $dentistaUser = User::create(['tenant_id' => $dentista->id, 'name' => 'D', 'email' => 'd@example.com', 'password' => bcrypt('x')]);
    $dentistaUser->assignRole(User::ROLE_OWNER);

    $this->actingAs($dentistaUser);
    $this->get('/scadenze')->assertOk()->assertSee('IMU acconto')->assertDontSee('730 scadenza');
});

it('un piano senza la funzione scadenze blocca il promemoria', function () {
    $this->tenant->update(['manual_plan' => 'starter']); // Starter: niente promemoria scadenze
    $this->actingAs($this->owner);

    Livewire::test(Deadlines::class)
        ->set("reminderTemplate.{$this->national->id}", 'imu')
        ->set("reminderDays.{$this->national->id}", 5)
        ->call('saveReminder', $this->national->id)
        ->assertDispatched('toast');

    expect(DeadlineReminder::withoutGlobalScopes()->count())->toBe(0);
});

it('il comando avvia una campagna per un promemoria dovuto', function () {
    Bus::fake();

    $deadline = Deadline::create(['tenant_id' => null, 'name' => 'IMU', 'due_date' => now()->addDays(5), 'active' => true]);
    $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => true]);
    DeadlineReminder::create([
        'tenant_id' => $this->tenant->id, 'deadline_id' => $deadline->id,
        'template_name' => 'promemoria_imu', 'days_before' => 7, 'active' => true,
    ]);

    $this->artisan('replisa:send-deadline-reminders')->assertSuccessful();

    Bus::assertDispatched(SendCampaign::class);
    expect(Campaign::withoutGlobalScopes()->count())->toBe(1)
        ->and(DeadlineReminder::withoutGlobalScopes()->first()->dispatched_at)->not->toBeNull();
});

it('il comando non avvia fuori dalla finestra di anticipo', function () {
    Bus::fake();

    $deadline = Deadline::create(['tenant_id' => null, 'name' => 'IMU', 'due_date' => now()->addDays(60), 'active' => true]);
    $this->tenant->contacts()->create(['phone' => '391', 'name' => 'Anna', 'opted_in' => true]);
    DeadlineReminder::create([
        'tenant_id' => $this->tenant->id, 'deadline_id' => $deadline->id,
        'template_name' => 'promemoria_imu', 'days_before' => 7, 'active' => true,
    ]);

    $this->artisan('replisa:send-deadline-reminders')->assertSuccessful();

    Bus::assertNotDispatched(SendCampaign::class);
});
