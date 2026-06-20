<?php

namespace App\Livewire;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Collegamento dell'account WhatsApp del tenant (onboarding interim, prima
 * dell'Embedded Signup Meta — ICE-11). Il tenant incolla manualmente
 * phone_number_id, waba_id e access_token (System User token permanente).
 * L'access_token è cast `encrypted` su {@see Tenant} (ADR-003) e non viene mai
 * ri-esposto: il campo si lascia vuoto per mantenere quello esistente.
 */
#[Layout('layouts.app')]
class WhatsAppSettings extends Component
{
    public string $phone_number_id = '';

    public string $waba_id = '';

    public string $access_token = '';

    public function mount(): void
    {
        $tenant = $this->tenant();

        $this->phone_number_id = $tenant?->phone_number_id ?? '';
        $this->waba_id = $tenant?->waba_id ?? '';
        // access_token resta vuoto: non si ri-mostra mai un segreto.
    }

    public function save(): void
    {
        $tenant = $this->tenant();

        if (! $tenant) {
            $this->dispatch('toast', type: 'error', message: 'Nessuna attività associata al tuo account.');

            return;
        }

        $validated = $this->validate([
            'phone_number_id' => ['required', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            // Token obbligatorio solo se non ne esiste già uno salvato.
            'access_token' => [$tenant->access_token ? 'nullable' : 'required', 'string'],
        ]);

        $tenant->phone_number_id = $validated['phone_number_id'];
        $tenant->waba_id = $validated['waba_id'] ?: null;

        if (filled($this->access_token)) {
            $tenant->access_token = $this->access_token;
        }

        $tenant->save();

        $this->access_token = '';
        $this->dispatch('toast', type: 'success', message: 'Credenziali WhatsApp salvate.');
    }

    /**
     * Verifica live le credenziali interrogando la Graph API di Meta
     * (info del numero), senza salvare nulla.
     */
    public function verify(): void
    {
        $tenant = $this->tenant();
        $token = filled($this->access_token) ? $this->access_token : $tenant?->access_token;
        $phoneNumberId = $this->phone_number_id ?: $tenant?->phone_number_id;

        if (! $token || ! $phoneNumberId) {
            $this->dispatch('toast', type: 'error', message: 'Inserisci Phone Number ID e Access Token prima di verificare.');

            return;
        }

        $version = config('services.meta.graph_version');

        try {
            $response = Http::withToken($token)
                ->get("https://graph.facebook.com/{$version}/{$phoneNumberId}", [
                    'fields' => 'display_phone_number,verified_name',
                ]);

            if ($response->successful()) {
                $number = $response->json('display_phone_number', $phoneNumberId);
                $this->dispatch('toast', type: 'success', message: "Connessione riuscita: {$number}.");

                return;
            }

            $metaError = $response->json('error.message', 'credenziali non valide');
            $this->dispatch('toast', type: 'error', message: "Verifica fallita: {$metaError}");
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Impossibile contattare Meta. Riprova tra poco.');
        }
    }

    protected function tenant(): ?Tenant
    {
        return auth()->user()?->tenant;
    }

    public function render(): View
    {
        $tenant = $this->tenant();

        return view('livewire.whatsapp-settings', [
            'isConnected' => (bool) ($tenant?->phone_number_id && $tenant?->access_token),
        ]);
    }
}
