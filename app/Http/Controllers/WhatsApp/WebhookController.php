<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    /**
     * Verifica webhook Meta (task 2.2.1). Meta invia hub.mode/hub.verify_token/
     * hub.challenge come query (PHP converte i `.` in `_`) e si aspetta indietro
     * il challenge in plain text.
     */
    public function verify(Request $request): Response
    {
        $verifyToken = (string) config('services.meta.webhook_verify_token');

        if ($request->query('hub_mode') === 'subscribe'
            && $verifyToken !== ''
            && hash_equals($verifyToken, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'))
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Ricezione eventi (task 2.2.2/2.2.6). La firma è già stata validata dal
     * middleware; il payload viene messo in coda e processato async, così
     * rispondiamo 200 subito (Meta ritenta se non rispondiamo in fretta).
     */
    public function handle(Request $request): Response
    {
        ProcessWhatsAppWebhook::dispatch($request->json()->all());

        return response('EVENT_RECEIVED');
    }
}
