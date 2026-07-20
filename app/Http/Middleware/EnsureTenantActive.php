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
            // Sull'API il redirect a una pagina HTML sarebbe illeggibile per un
            // client: stesso blocco, risposta nel formato che il chiamante attende.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Account sospeso: contatta l\'assistenza per riattivare il servizio.',
                ], 403);
            }

            return redirect()->route('suspended');
        }

        return $next($request);
    }
}
