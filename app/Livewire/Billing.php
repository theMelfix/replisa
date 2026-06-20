<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
    public function mount(): void
    {
        $outcome = request()->string('checkout')->toString();

        if ($outcome === 'success') {
            $this->dispatch('toast', type: 'success', message: 'Abbonamento attivato. Grazie!');
        } elseif ($outcome === 'cancelled') {
            $this->dispatch('toast', type: 'info', message: 'Pagamento annullato. Nessun addebito effettuato.');
        }
    }

    public function subscribe(string $plan): ?RedirectResponse
    {
        $config = config("plans.plans.$plan");

        if (! $config || empty($config['stripe_price_id'])) {
            $this->dispatch('toast', type: 'error', message: 'Piano non disponibile al momento.');

            return null;
        }

        $tenant = $this->tenant();

        if (! $tenant) {
            $this->dispatch('toast', type: 'error', message: 'Nessuna attività associata al tuo account.');

            return null;
        }

        try {
            return $tenant->newSubscription('default', $config['stripe_price_id'])
                ->checkout([
                    'success_url' => route('billing').'?checkout=success',
                    'cancel_url' => route('billing').'?checkout=cancelled',
                ])
                ->redirect();
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Impossibile avviare il pagamento. Riprova tra poco.');

            return null;
        }
    }

    public function manageBilling(): ?RedirectResponse
    {
        $tenant = $this->tenant();

        if (! $tenant || ! $tenant->hasStripeId()) {
            $this->dispatch('toast', type: 'error', message: 'Nessun abbonamento da gestire.');

            return null;
        }

        try {
            return $tenant->redirectToBillingPortal(route('billing'));
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Impossibile aprire la gestione abbonamento. Riprova.');

            return null;
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
