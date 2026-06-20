<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Billing per-tenant (E4.2.6): è il Tenant (l'attività) a sottoscrivere
        // l'abbonamento Stripe, non il singolo utente. La tabella subscriptions
        // usa quindi tenant_id (Subscription::owner() → Tenant::getForeignKey()).
        Cashier::useCustomerModel(Tenant::class);
    }
}
