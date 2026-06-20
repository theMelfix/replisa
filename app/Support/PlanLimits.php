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
        $price = $this->tenant->subscription('default')?->stripe_price;

        if ($price) {
            foreach (config('plans.plans', []) as $key => $plan) {
                if (($plan['stripe_price_id'] ?? null) === $price) {
                    return $key;
                }
            }
        }

        return config('plans.default', 'starter');
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
