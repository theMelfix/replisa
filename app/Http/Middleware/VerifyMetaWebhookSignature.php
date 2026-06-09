<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida la firma X-Hub-Signature-256 dei webhook Meta (task 2.2.3).
 *
 * La firma è `sha256=` + HMAC-SHA256 del body RAW usando l'App Secret
 * (credenziale app-level, ADR-003). Fail-closed: senza secret configurato
 * o firma non valida → 403, così nessuno può iniettare eventi falsi.
 */
class VerifyMetaWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSecret = (string) config('services.meta.app_secret');
        $header = (string) $request->header('X-Hub-Signature-256');

        if ($appSecret === '' || ! str_starts_with($header, 'sha256=')) {
            abort(403, 'Firma webhook mancante o non configurata.');
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! hash_equals($expected, $header)) {
            abort(403, 'Firma webhook non valida.');
        }

        return $next($request);
    }
}
