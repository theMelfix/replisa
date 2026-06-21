<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
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
            'mrr' => $mrr,
        ]);
    }
}
