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
