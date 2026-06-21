<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Attivazione account su invito (E5): raggiunta tramite URL firmato dall'email
 * {@see \App\Notifications\TenantInvitation}. Il cliente imposta la password e
 * accede. Invito monouso: dopo l'attivazione (email_verified_at valorizzato) il
 * link mostra un avviso.
 */
#[Layout('layouts.guest')]
class AcceptInvitation extends Component
{
    public User $user;

    public bool $used = false;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->used = (bool) $user->email_verified_at;
    }

    public function activate(): void
    {
        if ($this->used) {
            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->user->forceFill([
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ])->save();

        Auth::login($this->user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.accept-invitation');
    }
}
