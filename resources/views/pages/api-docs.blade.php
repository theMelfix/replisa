<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Guida all'API</h2>
    </x-slot>

    @php($base = url('/api/v1'))

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 prose-sm dark:text-gray-300 max-w-none">
                <p class="text-gray-700 dark:text-gray-300">
                    L'API di Replisa permette al tuo gestionale di inviare messaggi e creare appuntamenti
                    (con promemoria automatico). Tutte le richieste vanno autenticate con una chiave
                    che generi nella pagina <a href="{{ route('api-tokens') }}" class="text-green-700 dark:text-green-500 underline font-semibold" wire:navigate>API &amp; Integrazioni</a>.
                </p>
            </div>

            <section class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Autenticazione</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Includi la chiave nell'header <code>Authorization</code> di ogni richiesta:
                </p>
                <pre class="bg-gray-900 text-gray-100 text-xs rounded-md p-4 overflow-x-auto"><code>Authorization: Bearer LA_TUA_CHIAVE
Accept: application/json</code></pre>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                    URL base: <code class="text-green-700 dark:text-green-500">{{ $base }}</code> ·
                    Limite: 60 richieste al minuto per chiave.
                </p>
            </section>

            <section class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-bold text-white bg-green-600 rounded px-2 py-0.5">POST</span>
                    <code class="text-sm text-gray-800 dark:text-gray-200">/messages/send</code>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Invia un messaggio. Usa <code>type: "text"</code> solo se il cliente ti ha scritto nelle
                    ultime 24 ore; altrimenti usa <code>type: "template"</code> con un modello approvato.
                </p>
                <pre class="bg-gray-900 text-gray-100 text-xs rounded-md p-4 overflow-x-auto"><code>curl -X POST {{ $base }}/messages/send \
  -H "Authorization: Bearer LA_TUA_CHIAVE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "393334445566",
    "type": "template",
    "template": "appointment_reminder",
    "language": "it",
    "params": ["Mario Rossi", "24/07/2026", "15:30"]
  }'</code></pre>
            </section>

            <section class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-bold text-white bg-green-600 rounded px-2 py-0.5">POST</span>
                    <code class="text-sm text-gray-800 dark:text-gray-200">/appointments</code>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Crea un appuntamento. Il promemoria parte in automatico secondo la configurazione del
                    flusso Promemoria. Se il numero non è tra i tuoi contatti, viene creato.
                </p>
                <pre class="bg-gray-900 text-gray-100 text-xs rounded-md p-4 overflow-x-auto"><code>curl -X POST {{ $base }}/appointments \
  -H "Authorization: Bearer LA_TUA_CHIAVE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "393334445566",
    "name": "Mario Rossi",
    "scheduled_at": "2026-07-25T15:30:00"
  }'</code></pre>
            </section>

            <section class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-bold text-white bg-gray-500 rounded px-2 py-0.5">GET</span>
                    <code class="text-sm text-gray-800 dark:text-gray-200">/messages</code>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Elenca i messaggi (paginati, più recenti prima). Filtri opzionali:
                    <code>direction</code> (inbound/outbound), <code>status</code>, <code>per_page</code> (max 100).
                </p>
                <pre class="bg-gray-900 text-gray-100 text-xs rounded-md p-4 overflow-x-auto"><code>curl "{{ $base }}/messages?direction=outbound&per_page=50" \
  -H "Authorization: Bearer LA_TUA_CHIAVE" \
  -H "Accept: application/json"</code></pre>
            </section>

        </div>
    </div>
</x-app-layout>
