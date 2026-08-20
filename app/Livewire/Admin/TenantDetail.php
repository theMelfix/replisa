<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantInvitation;
use App\Rules\ItalianVatChecksum;
use App\Services\WhatsApp\ConnectionCheck;
use App\Support\ItalianVat;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Scheda di un singolo cliente per il super-admin.
 *
 * Prima della sua esistenza il tenant viveva tutto in una riga della tabella
 * `/admin/tenants`: dopo la creazione l'unico campo modificabile era il settore,
 * l'invito non si poteva rispedire e le credenziali Meta le poteva inserire solo
 * il cliente da `/whatsapp` — anche se la checklist di onboarding prevede che i
 * dati Meta li raccolga l'admin. Tutto quello che serve per seguire un cliente
 * sta qui: anagrafica e dati fiscali, utenti con invito, credenziali WhatsApp
 * con verifica, riepilogo di piano e abbonamento.
 *
 * Le azioni distruttive (blocco, disdetta, rimborso, licenze) restano
 * volutamente nell'elenco: qui si guarda e si corregge, non si smonta.
 */
#[Layout('layouts.app')]
class TenantDetail extends Component
{
    public Tenant $tenant;

    // Anagrafica e dati fiscali.
    public string $name = '';

    public string $sector = '';

    public string $vat_number = '';

    public string $tax_code = '';

    public string $address = '';

    public string $city = '';

    public string $postal_code = '';

    public string $province = '';

    public string $country = '';

    public string $sdi_code = '';

    public string $pec = '';

    // Credenziali Meta.
    public string $phone_number_id = '';

    public string $waba_id = '';

    /** Write-only: un segreto salvato non si ri-mostra mai (ADR-003). */
    public string $access_token = '';

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;

        $this->name = $tenant->name ?? '';
        $this->sector = $tenant->sector ?? '';
        $this->vat_number = $tenant->vat_number ?? '';
        $this->tax_code = $tenant->tax_code ?? '';
        $this->address = $tenant->address ?? '';
        $this->city = $tenant->city ?? '';
        $this->postal_code = $tenant->postal_code ?? '';
        $this->province = $tenant->province ?? '';
        $this->country = $tenant->country ?? 'IT';
        $this->sdi_code = $tenant->sdi_code ?? '';
        $this->pec = $tenant->pec ?? '';

        $this->phone_number_id = $tenant->phone_number_id ?? '';
        $this->waba_id = $tenant->waba_id ?? '';
    }

    /**
     * Salva anagrafica e dati fiscali.
     *
     * A differenza della registrazione self-service qui i campi fiscali sono
     * facoltativi: un tenant censito dall'admin nasce spesso senza, e imporli
     * bloccherebbe anche la semplice correzione di una ragione sociale. La
     * P.IVA, se c'è, è validata nel formato ma non su VIES — l'admin è fidato,
     * stessa scelta di {@see Tenants::createTenant()}.
     */
    public function saveProfile(): void
    {
        $this->vat_number = ItalianVat::normalize($this->vat_number);
        $this->province = mb_strtoupper(trim($this->province));
        $this->country = mb_strtoupper(trim($this->country)) ?: 'IT';
        $this->sdi_code = mb_strtoupper(trim($this->sdi_code));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sector' => ['required', 'in:'.implode(',', array_keys(config('sectors')))],
            'vat_number' => [
                'nullable', 'string', new ItalianVatChecksum,
                Rule::unique('tenants', 'vat_number')->ignore($this->tenant->id),
            ],
            'tax_code' => ['nullable', 'string', 'max:16'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'regex:/^\d{5}$/'],
            'province' => ['nullable', 'regex:/^[A-Z]{2}$/'],
            'country' => ['required', 'regex:/^[A-Z]{2}$/'],
            'sdi_code' => ['nullable', 'regex:/^[A-Z0-9]{6,7}$/'],
            'pec' => ['nullable', 'email', 'max:255'],
        ], attributes: [
            'name' => 'ragione sociale',
            'vat_number' => 'Partita IVA',
            'tax_code' => 'codice fiscale',
            'postal_code' => 'CAP',
            'province' => 'provincia',
            'country' => 'paese',
            'sdi_code' => 'codice destinatario SDI',
        ]);

        // I campi facoltativi vuoti tornano a NULL: '' su una colonna unique
        // come vat_number farebbe collidere due tenant senza Partita IVA.
        $this->tenant->update([
            'name' => $validated['name'],
            'sector' => $validated['sector'],
            'vat_number' => $validated['vat_number'] ?: null,
            'tax_code' => $validated['tax_code'] ?: null,
            'address' => $validated['address'] ?: null,
            'city' => $validated['city'] ?: null,
            'postal_code' => $validated['postal_code'] ?: null,
            'province' => $validated['province'] ?: null,
            'country' => $validated['country'],
            'sdi_code' => $validated['sdi_code'] ?: null,
            'pec' => $validated['pec'] ?: null,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Dati del cliente aggiornati.');
    }

    /**
     * Salva le credenziali Meta al posto del cliente (onboarding assistito).
     * Il token si lascia vuoto per mantenere quello già salvato.
     */
    public function saveWhatsApp(): void
    {
        $validated = $this->validate([
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
        ], attributes: [
            'phone_number_id' => 'Phone Number ID',
            'waba_id' => 'WABA ID',
            'access_token' => 'access token',
        ]);

        $this->tenant->phone_number_id = $validated['phone_number_id'] ?: null;
        $this->tenant->waba_id = $validated['waba_id'] ?: null;

        if (filled($this->access_token)) {
            $this->tenant->access_token = $this->access_token;
        }

        $this->tenant->save();

        $this->access_token = '';
        $this->dispatch('toast', type: 'success', message: 'Credenziali WhatsApp salvate.');
    }

    public function verifyWhatsApp(ConnectionCheck $check): void
    {
        $result = $check->run(
            $this->phone_number_id ?: $this->tenant->phone_number_id,
            filled($this->access_token) ? $this->access_token : $this->tenant->access_token,
        );

        $this->dispatch('toast', type: $result['ok'] ? 'success' : 'error', message: $result['message']);
    }

    /**
     * Rispedisce l'email di attivazione. Serve più spesso di quanto sembri: il
     * link firmato dura 7 giorni ({@see TenantInvitation}) e finora, scaduto
     * quello, l'unico modo di sbloccare il cliente era la tinker sul VPS.
     */
    public function resendInvitation(int $userId): void
    {
        // findOrFail sulla relazione: un id di un altro tenant non è raggiungibile.
        $user = $this->tenant->users()->findOrFail($userId);

        if ($user->email_verified_at) {
            $this->dispatch('toast', type: 'info', message: "{$user->name} ha già attivato l'account: usa il recupero password.");

            return;
        }

        $user->notify(new TenantInvitation);

        $this->dispatch('toast', type: 'success', message: "Invito rispedito a {$user->email}.");
    }

    public function render(): View
    {
        $limits = PlanLimits::for($this->tenant);
        $subscription = $this->tenant->subscription('default');

        return view('livewire.admin.tenant-detail', [
            'users' => $this->tenant->users()->with('roles')->orderBy('name')->get(),
            'sectors' => config('sectors'),
            'planName' => $limits->planName(),
            'planSource' => $limits->planSource(),
            'contactsCount' => $this->tenant->contacts()->count(),
            'messagesCount' => $this->tenant->messages()->count(),
            'subscriptionStatus' => $subscription?->stripe_status,
            'subscriptionEndsAt' => $subscription?->ends_at,
            'ownerRole' => User::ROLE_OWNER,
        ]);
    }
}
