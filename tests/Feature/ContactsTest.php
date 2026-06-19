<?php

use App\Livewire\Contacts;
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

    $this->tenant->contacts()->create(['phone' => '393331110001', 'name' => 'Anna']);
    $this->tenant->contacts()->create(['phone' => '393331110002', 'name' => 'Marco']);
    $this->other->contacts()->create(['phone' => '393339990003', 'name' => 'Estraneo']);

    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/contacts')->assertRedirect(route('login'));
});

it('mostra solo i contatti del proprio tenant', function () {
    $this->actingAs($this->owner);

    Livewire::test(Contacts::class)
        ->assertSee('Anna')
        ->assertSee('Marco')
        ->assertDontSee('Estraneo');
});

it('filtra per ricerca senza far trapelare altri tenant', function () {
    $this->actingAs($this->owner);

    Livewire::test(Contacts::class)
        ->set('search', 'Anna')
        ->assertSee('Anna')
        ->assertDontSee('Marco');
});
