<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Gestione delle API key del tenant (E4.3.1): l'owner genera e revoca i token
 * personali Sanctum usati per l'API pubblica v1. Il token in chiaro è mostrato
 * una sola volta, subito dopo la creazione (Sanctum salva solo l'hash).
 */
#[Layout('layouts.app')]
class ApiTokens extends Component
{
    public string $name = '';

    /** Il token in chiaro appena generato, mostrato una sola volta. */
    public ?string $plainTextToken = null;

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
        ], attributes: ['name' => 'nome']);

        $user = auth()->user();

        // `abilities` lasciate a ['*']: l'autorizzazione reale è per-tenant
        // (TenantScope) + tenant.active, non per-abilità.
        $token = $user->createToken(trim($this->name));

        $this->plainTextToken = $token->plainTextToken;
        $this->reset('name');
    }

    public function revoke(int $tokenId): void
    {
        // Scoped all'utente: non si può revocare il token di un altro.
        auth()->user()->tokens()->whereKey($tokenId)->delete();

        $this->plainTextToken = null;
    }

    /** @return Collection<int, PersonalAccessToken> */
    public function getTokensProperty(): Collection
    {
        return auth()->user()->tokens()->latest()->get();
    }

    public function render(): View
    {
        return view('livewire.api-tokens', [
            'tokens' => $this->tokens,
        ]);
    }
}
