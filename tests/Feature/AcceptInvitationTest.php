<?php

use App\Livewire\AcceptInvitation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/** Utente appena censito dall'admin: password casuale, email non ancora verificata. */
function invitedUser(): User
{
    $tenant = Tenant::create(['name' => 'Studio']);

    return User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mario',
        'email' => 'mario@example.com',
        'password' => bcrypt('temporanea'),
    ]);
}

it('un URL firmato valido mostra il form di attivazione', function () {
    $user = invitedUser();
    $url = URL::temporarySignedRoute('invitation.accept', now()->addDay(), ['user' => $user->id]);

    $this->get($url)->assertOk()->assertSee('Attiva account');
});

it('un URL senza firma valida è rifiutato', function () {
    $user = invitedUser();

    $this->get(route('invitation.accept', $user))->assertForbidden();
});

it('il cliente imposta la password e accede', function () {
    $user = invitedUser();

    Livewire::test(AcceptInvitation::class, ['user' => $user])
        ->set('password', 'password-123')
        ->set('password_confirmation', 'password-123')
        ->call('activate')
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('un invito già usato mostra un avviso', function () {
    $user = invitedUser();
    $user->forceFill(['email_verified_at' => now()])->save();

    $url = URL::temporarySignedRoute('invitation.accept', now()->addDay(), ['user' => $user->id]);

    $this->get($url)->assertOk()->assertSee('già stato utilizzato');
});
