<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Rate limiter dell'API pubblica (E4.3): 60 richieste/minuto per token
        // (o per IP se non autenticato). Il throttling di Meta sull'invio resta
        // gestito a valle da WhatsAppService (retry/backoff).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
