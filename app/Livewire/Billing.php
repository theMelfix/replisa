<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Models\Message;
use App\Models\Tenant;
use App\Support\PlanLimits;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Abbonamento del tenant (E4.2.6). Mostra i piani e l'abbonamento corrente;
 * l'attivazione/cambio piano passa da Stripe Checkout (ospitato), la gestione
 * carta/disdetta dal Billing Portal di Stripe. Billable = Tenant.
 */
#[Layout('layouts.app')]
class Billing extends Component
{
    /** Periodo di fatturazione scelto per il checkout: 'monthly' | 'annual'. */
    public string $period = 'monthly';

    public function mount(): void
    {
        $outcome = request()->string('checkout')->toString();

        if ($outcome === 'success') {
            $this->dispatch('toast', type: 'success', message: 'Abbonamento attivato. Grazie!');
        } elseif ($outcome === 'cancelled') {
            $this->dispatch('toast', type: 'info', message: 'Pagamento annullato. Nessun addebito effettuato.');
        }
    }

    public function subscribe(string $plan): void
    {
        $config = config("plans.plans.$plan");
        $priceId = $this->period === 'annual'
            ? ($config['stripe_price_id_annual'] ?? null)
            : ($config['stripe_price_id'] ?? null);

        if (! $config || empty($priceId)) {
            $this->dispatch('toast', type: 'error', message: 'Piano non disponibile al momento.');

            return;
        }

        $tenant = $this->tenant();

        if (! $tenant) {
            $this->dispatch('toast', type: 'error', message: 'Nessuna attività associata al tuo account.');

            return;
        }

        try {
            // NB: non usare Checkout::redirect() qui — in contesto Livewire il
            // facade Redirect restituisce un Livewire\...\Redirector e viola il
            // return-type `RedirectResponse` di Cashier (TypeError). Estraiamo
            // l'URL della sessione (Checkout::__get → session->url) e usiamo il
            // redirect di Livewire, che gestisce anche gli URL esterni.
            $checkout = $tenant->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('billing').'?checkout=success',
                    'cancel_url' => route('billing').'?checkout=cancelled',
                ]);

            $this->redirect($checkout->url);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Impossibile avviare il pagamento. Riprova tra poco.');
        }
    }

    public function manageBilling(): void
    {
        $tenant = $this->tenant();

        if (! $tenant || ! $tenant->hasStripeId()) {
            $this->dispatch('toast', type: 'error', message: 'Nessun abbonamento da gestire.');

            return;
        }

        try {
            // Stesso motivo di subscribe(): redirectToBillingPortal() ritorna un
            // RedirectResponse incompatibile col ciclo Livewire. Usiamo l'URL.
            $this->redirect($tenant->billingPortalUrl(route('billing')));
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Impossibile aprire la gestione abbonamento. Riprova.');
        }
    }

    protected function tenant(): ?Tenant
    {
        return auth()->user()?->tenant;
    }

    public function render(): View
    {
        $tenant = $this->tenant();
        $subscription = $tenant?->subscription('default');

        return view('livewire.billing', [
            'plans' => config('plans.plans'),
            'subscription' => $subscription,
            'currentPriceId' => $subscription?->stripe_price,
            'usage' => $tenant ? $this->usage($tenant) : null,
            'contactsLimit' => $tenant ? PlanLimits::for($tenant)->contactsLimit() : null,
            'campaignLimit' => $tenant ? PlanLimits::for($tenant)->campaignMessagesLimit() : null,
            'campaignUsed' => $tenant ? Campaign::where('created_at', '>=', now()->startOfMonth())->sum('total') : 0,
            'onTrial' => (bool) $tenant?->onGenericTrial(),
            'trialEndsAt' => $tenant?->onGenericTrial() ? $tenant->trial_ends_at : null,
        ]);
    }

    /**
     * Conteggio di utilizzo del mese corrente (E4.2.6). Periodo = mese di
     * calendario; il modello di costo Meta è per-conversazione, ma per la
     * dashboard cliente conta soprattutto il volume di messaggi.
     *
     * @return array{period_label: string, sent: int, received: int, contacts: int}
     */
    protected function usage(Tenant $tenant): array
    {
        $periodStart = now()->startOfMonth();

        return [
            'period_label' => $periodStart->translatedFormat('F Y'),
            'sent' => $tenant->messages()
                ->where('direction', Message::DIRECTION_OUTBOUND)
                ->where('created_at', '>=', $periodStart)
                ->count(),
            'received' => $tenant->messages()
                ->where('direction', Message::DIRECTION_INBOUND)
                ->where('created_at', '>=', $periodStart)
                ->count(),
            'contacts' => $tenant->contacts()->count(),
        ];
    }
}
