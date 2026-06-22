<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Risoluzione del piano attivo di un tenant e dei suoi limiti (E4.2.6).
 *
 * Il piano si deriva dal prezzo dell'abbonamento Stripe attivo; in assenza di
 * abbonamento si usa il piano di default (`config('plans.default')`, Starter),
 * così i limiti valgono anche per i tenant non ancora abbonati / in onboarding.
 * Un limite `null` significa illimitato.
 */
class PlanLimits
{
    public function __construct(private Tenant $tenant) {}

    public static function for(Tenant $tenant): self
    {
        return new self($tenant);
    }

    public function planKey(): string
    {
        // 1) Licenza offline assegnata dall'admin: ha priorità sullo Stripe.
        if ($this->tenant->hasActiveOfflineLicense() && config('plans.plans.'.$this->tenant->manual_plan)) {
            return $this->tenant->manual_plan;
        }

        // 2) Abbonamento Stripe attivo.
        $price = $this->tenant->subscription('default')?->stripe_price;

        if ($price) {
            foreach (config('plans.plans', []) as $key => $plan) {
                if (($plan['stripe_price_id'] ?? null) === $price) {
                    return $key;
                }
            }
        }

        // 3) Default (onboarding/free tier).
        return config('plans.default', 'starter');
    }

    /** Origine del piano effettivo: 'offline' | 'stripe' | 'default'. */
    public function planSource(): string
    {
        if ($this->tenant->hasActiveOfflineLicense() && config('plans.plans.'.$this->tenant->manual_plan)) {
            return 'offline';
        }

        return $this->tenant->subscription('default')?->stripe_price ? 'stripe' : 'default';
    }

    /** Canone mensile del piano effettivo (per il calcolo MRR in admin). */
    public function price(): int
    {
        return (int) config('plans.plans.'.$this->planKey().'.price', 0);
    }

    /** Il piano effettivo include la funzione richiesta (es. 'campaigns', 'deadlines')? */
    public function allows(string $feature): bool
    {
        return (bool) config('plans.plans.'.$this->planKey().'.features.'.$feature, false);
    }

    /** Il tenant ha l'add-on Recensioni: incluso nel piano (Business) o concesso. */
    public function hasReviewsAddon(): bool
    {
        $includedIn = config('plans.addons.reviews.included_in', []);

        return in_array($this->planKey(), $includedIn, true) || (bool) $this->tenant->reviews_addon;
    }

    public function planName(): string
    {
        return config('plans.plans.'.$this->planKey().'.name', ucfirst($this->planKey()));
    }

    public function limit(string $resource): ?int
    {
        return config('plans.plans.'.$this->planKey().'.limits.'.$resource);
    }

    public function contactsLimit(): ?int
    {
        return $this->limit('contacts');
    }

    public function automationsLimit(): ?int
    {
        return $this->limit('automations');
    }

    /** Può aggiungere $count nuovi contatti restando entro il limite del piano? */
    public function canAddContacts(int $count = 1): bool
    {
        $limit = $this->contactsLimit();

        if ($limit === null) {
            return true;
        }

        return $this->tenant->contacts()->count() + $count <= $limit;
    }

    /** Può attivare un'altra automazione restando entro il limite del piano? */
    public function canActivateAnotherAutomation(): bool
    {
        $limit = $this->automationsLimit();

        if ($limit === null) {
            return true;
        }

        return $this->tenant->automations()->where('active', true)->count() < $limit;
    }
}
