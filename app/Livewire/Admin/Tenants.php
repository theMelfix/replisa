<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantInvitation;
use App\Rules\ItalianVatChecksum;
use App\Support\ItalianVat;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Area super-admin (E4/E5): elenco di tutti i tenant con stato abbonamento,
 * dati fiscali, MRR e azioni — blocco/sblocco, disdetta e rimborso Stripe,
 * assegnazione/revoca di licenze offline. Protetta da `role:super-admin`.
 */
#[Layout('layouts.app')]
class Tenants extends Component
{
    /** @var array<int, string> piano scelto per l'assegnazione licenza, per tenant id */
    public array $licensePlan = [];

    /** @var array<int, string> scadenza opzionale (YYYY-MM-DD) della licenza, per tenant id */
    public array $licenseExpiry = [];

    /** @var array<int, string> settore selezionato per tenant id (modifica inline) */
    public array $sectorInput = [];

    public function mount(): void
    {
        $this->sectorInput = Tenant::pluck('sector', 'id')->map(fn (?string $s) => $s ?? '')->all();
    }

    public function updateSector(int $tenantId): void
    {
        $sector = $this->sectorInput[$tenantId] ?? '';

        if ($sector !== '' && ! array_key_exists($sector, config('sectors'))) {
            $this->dispatch('toast', type: 'error', message: 'Settore non valido.');

            return;
        }

        Tenant::findOrFail($tenantId)->update(['sector' => $sector ?: null]);
        $this->dispatch('toast', type: 'success', message: 'Settore aggiornato.');
    }

    // Form "Nuovo cliente" (censimento da admin).
    public string $newBusinessName = '';

    public string $newSector = '';

    public string $newVatNumber = '';

    public string $newOwnerName = '';

    public string $newOwnerEmail = '';

    public string $newPlan = '';

    public string $newPlanExpiry = '';

    /**
     * Censimento manuale di un nuovo cliente (E5): crea il tenant e il suo
     * utente owner, con eventuale licenza offline. La P.IVA è opzionale e, se
     * presente, validata solo nel formato (l'admin è fidato: niente VIES). Il
     * cliente riceve un'email d'invito per impostare la propria password.
     */
    public function createTenant(): void
    {
        $this->newVatNumber = ItalianVat::normalize($this->newVatNumber);

        $validated = $this->validate([
            'newBusinessName' => ['required', 'string', 'max:255'],
            'newSector' => ['required', 'in:'.implode(',', array_keys(config('sectors')))],
            'newVatNumber' => ['nullable', 'string', new ItalianVatChecksum, 'unique:tenants,vat_number'],
            'newOwnerName' => ['required', 'string', 'max:255'],
            'newOwnerEmail' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'newPlan' => ['nullable', 'in:'.implode(',', array_keys(config('plans.plans')))],
            'newPlanExpiry' => ['nullable', 'date'],
        ], attributes: [
            'newBusinessName' => 'ragione sociale',
            'newVatNumber' => 'Partita IVA',
            'newOwnerName' => 'nome referente',
            'newOwnerEmail' => 'email',
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $tenant = Tenant::create([
                'name' => $validated['newBusinessName'],
                'sector' => $validated['newSector'],
                'vat_number' => $validated['newVatNumber'] ?: null,
                'manual_plan' => $validated['newPlan'] ?: null,
                'manual_plan_expires_at' => $validated['newPlan'] && $validated['newPlanExpiry']
                    ? Carbon::parse($validated['newPlanExpiry'])->endOfDay()
                    : null,
            ]);

            // Password casuale: il cliente imposterà la sua tramite il link d'invito.
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['newOwnerName'],
                'email' => $validated['newOwnerEmail'],
                'password' => Hash::make(Str::random(40)),
            ]);
            $user->assignRole(User::ROLE_OWNER);

            return $user;
        });

        $user->notify(new TenantInvitation);

        $this->reset('newBusinessName', 'newSector', 'newVatNumber', 'newOwnerName', 'newOwnerEmail', 'newPlan', 'newPlanExpiry');
        $this->dispatch('tenant-created');
        $this->dispatch('toast', type: 'success', message: "Cliente creato. Invito inviato a {$user->email}.");
    }

    public function toggle(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update(['active' => ! $tenant->active]);
    }

    public function assignLicense(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $plan = $this->licensePlan[$tenantId] ?? null;

        if (! $plan || ! config("plans.plans.$plan")) {
            $this->dispatch('toast', type: 'error', message: 'Seleziona un piano valido.');

            return;
        }

        $expiry = $this->licenseExpiry[$tenantId] ?? null;

        $tenant->update([
            'manual_plan' => $plan,
            'manual_plan_expires_at' => $expiry ? Carbon::parse($expiry)->endOfDay() : null,
        ]);

        $this->dispatch('toast', type: 'success', message: "Licenza {$plan} assegnata a {$tenant->name}.");
    }

    public function toggleReviewsAddon(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update(['reviews_addon' => ! $tenant->reviews_addon]);

        $this->dispatch('toast', type: 'success', message: $tenant->reviews_addon
            ? 'Add-on Recensioni attivato.'
            : 'Add-on Recensioni disattivato.');
    }

    public function revokeLicense(int $tenantId): void
    {
        Tenant::findOrFail($tenantId)->update(['manual_plan' => null, 'manual_plan_expires_at' => null]);

        $this->dispatch('toast', type: 'info', message: 'Licenza offline revocata.');
    }

    public function cancelSubscription(int $tenantId): void
    {
        $subscription = Tenant::findOrFail($tenantId)->subscription('default');

        if (! $subscription) {
            $this->dispatch('toast', type: 'error', message: 'Nessun abbonamento Stripe da disdire.');

            return;
        }

        try {
            $subscription->cancelNow();
            $this->dispatch('toast', type: 'success', message: 'Abbonamento disdetto.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Errore nella disdetta su Stripe.');
        }
    }

    public function refundLast(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);

        if (! $tenant->hasStripeId()) {
            $this->dispatch('toast', type: 'error', message: 'Nessun pagamento Stripe da rimborsare.');

            return;
        }

        try {
            $invoice = $tenant->invoices()->first();

            if (! $invoice) {
                $this->dispatch('toast', type: 'error', message: 'Nessuna fattura trovata.');

                return;
            }

            $tenant->refund($invoice->payment_intent);
            $this->dispatch('toast', type: 'success', message: 'Ultimo pagamento rimborsato.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Errore nel rimborso su Stripe.');
        }
    }

    public function render(): View
    {
        $tenants = Tenant::withCount(['contacts', 'messages'])->orderBy('name')->get();

        $mrr = $tenants->sum(fn (Tenant $t) => PlanLimits::for($t)->planSource() === 'default'
            ? 0
            : PlanLimits::for($t)->price());

        return view('livewire.admin.tenants', [
            'tenants' => $tenants,
            'plans' => config('plans.plans'),
            'sectors' => config('sectors'),
            'mrr' => $mrr,
        ]);
    }
}
