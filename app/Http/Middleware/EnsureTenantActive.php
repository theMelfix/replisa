<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocca l'accesso alle pagine del tenant quando l'attività è disattivata
 * dall'admin (E4/E5: "blocca abbonamento"). I super-admin (senza tenant)
 * passano sempre.
 */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;

        if ($tenant && ! $tenant->active) {
            return redirect()->route('suspended');
        }

        return $next($request);
    }
}
