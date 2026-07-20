<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Base dei controller API v1 (E4.3). Il token Sanctum è emesso per un `User`;
 * l'API opera però sempre nel contesto del suo tenant. Qui lo risolviamo una
 * volta sola: l'isolamento dati sulle query resta garantito dal TenantScope
 * (che legge lo stesso `user()->tenant_id` dell'utente autenticato via token).
 */
abstract class ApiController extends Controller
{
    /**
     * Il tenant dell'utente autenticato. Un token appartenente a un utente
     * senza tenant (es. super-admin) non ha un contesto su cui operare via API.
     */
    protected function tenant(Request $request): Tenant
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant) {
            throw new HttpException(403, 'Questo token non è associato a nessun account.');
        }

        return $tenant;
    }
}
