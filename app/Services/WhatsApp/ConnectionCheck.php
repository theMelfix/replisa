<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

/**
 * Verifica live delle credenziali Meta di un tenant: interroga la Graph API
 * chiedendo le info del numero, senza salvare nulla e senza inviare messaggi.
 *
 * Sta in un servizio a sé perché serve da due parti — il cliente che collega il
 * proprio numero in `/whatsapp` e il super-admin che lo fa per lui dal dettaglio
 * cliente durante l'onboarding assistito. Un'unica implementazione significa
 * anche un unico posto dove tradurre gli errori di Meta in italiano.
 */
class ConnectionCheck
{
    /**
     * @return array{ok: bool, message: string} esito e messaggio già pronto per il toast
     */
    public function run(?string $phoneNumberId, ?string $accessToken): array
    {
        if (! filled($accessToken) || ! filled($phoneNumberId)) {
            return [
                'ok' => false,
                'message' => 'Inserisci Phone Number ID e Access Token prima di verificare.',
            ];
        }

        $version = config('services.meta.graph_version');

        try {
            $response = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/{$version}/{$phoneNumberId}", [
                    'fields' => 'display_phone_number,verified_name',
                ]);

            if ($response->successful()) {
                $number = $response->json('display_phone_number', $phoneNumberId);

                return ['ok' => true, 'message' => "Connessione riuscita: {$number}."];
            }

            $metaError = $response->json('error.message', 'credenziali non valide');

            return ['ok' => false, 'message' => "Verifica fallita: {$metaError}"];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'Impossibile contattare Meta. Riprova tra poco.'];
        }
    }
}
