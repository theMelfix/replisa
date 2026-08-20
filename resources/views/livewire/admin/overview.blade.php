@php
    // Un solo posto in cui decidere se la sezione "Richiede attenzione" ha qualcosa da dire.
    $hasAlerts = $failuresCount > 0
        || $failedJobsCount > 0
        || $notConnected->isNotEmpty()
        || $expiringTrials->isNotEmpty()
        || $expiredLicenses->isNotEmpty();
@endphp

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Panoramica</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Stato della piattaforma al {{ now()->format('d/m/Y H:i') }}.
            </p>
        </div>

        {{-- Numeri di piattaforma --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Clienti attivi', $activeCount, $blockedCount > 0 ? $blockedCount.' bloccati' : null],
                ['In prova', $trialCount, null],
                ['MRR stimato', '€'.number_format($mrr, 0, ',', '.'), 'al mese'],
                ['Messaggi inviati', $messagesThisMonth, 'questo mese'],
            ] as [$label, $value, $hint])
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $value }}</p>
                    @if ($hint)
                        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Segnali --}}
        <div>
            <h2 class="mb-4 font-semibold text-gray-900 dark:text-gray-100">Richiede attenzione</h2>

            @unless ($hasAlerts)
                <div class="rounded-lg border border-green-200 bg-green-50 p-6 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    Tutto in ordine: nessun messaggio fallito negli ultimi {{ \App\Livewire\Admin\Overview::FAILURE_DAYS }} giorni,
                    nessun job in errore, tutti i clienti collegati.
                </div>
            @endunless

            <div class="grid gap-4 lg:grid-cols-2">

                {{-- Messaggi falliti --}}
                @if ($failuresCount > 0)
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Messaggi falliti</h3>
                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-500/15 dark:text-red-400">
                                {{ $failuresCount }} in {{ \App\Livewire\Admin\Overview::FAILURE_DAYS }} giorni
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Causa tipica: token Meta scaduto, finestra 24h chiusa, template non approvato.
                        </p>

                        <ul class="mt-4 space-y-3">
                            @foreach ($recentFailures as $message)
                                <li class="border-l-2 border-red-300 pl-3">
                                    <div class="flex flex-wrap items-baseline gap-x-2 text-sm">
                                        @if ($message->tenant)
                                            <a href="{{ route('admin.tenants.show', $message->tenant) }}" wire:navigate
                                               class="font-medium text-green-700 hover:underline dark:text-green-400">
                                                {{ $message->tenant->name }}
                                            </a>
                                        @else
                                            <span class="font-medium text-gray-500">Cliente rimosso</span>
                                        @endif
                                        <span class="text-xs text-gray-400">{{ $message->created_at?->format('d/m H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $message->errorSummary() ?? 'Errore non registrato' }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Job falliti --}}
                @if ($failedJobsCount > 0)
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Job falliti</h3>
                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-500/15 dark:text-red-400">
                                {{ $failedJobsCount }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Promemoria, campagne e webhook girano in coda: un job fallito è un'automazione che non è partita.
                        </p>

                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($recentFailedJobs as $job)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-3 border-l-2 border-red-300 pl-3">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $job['name'] }}</span>
                                    <span class="text-xs text-gray-400">{{ $job['failed_at'] }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <p class="mt-4 text-xs text-gray-500">
                            Dettaglio e rilancio dal VPS: <code>php artisan queue:failed</code> · <code>queue:retry</code>
                        </p>
                    </div>
                @endif

                {{-- Clienti senza WhatsApp --}}
                @if ($notConnected->isNotEmpty())
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Clienti senza WhatsApp collegato</h3>
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                {{ $notConnected->count() }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Senza Phone Number ID e token la Cloud API non può inviare nulla: nessuna automazione parte.
                        </p>

                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($notConnected as $tenant)
                                <li>
                                    <a href="{{ route('admin.tenants.show', $tenant) }}" wire:navigate
                                       class="text-green-700 hover:underline dark:text-green-400">{{ $tenant->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Prove in scadenza --}}
                @if ($expiringTrials->isNotEmpty())
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Prove in scadenza</h3>
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                {{ $expiringTrials->count() }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Entro {{ \App\Livewire\Admin\Overview::TRIAL_WARNING_DAYS }} giorni. È il momento di chiamare.
                        </p>

                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($expiringTrials as $tenant)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-3">
                                    <a href="{{ route('admin.tenants.show', $tenant) }}" wire:navigate
                                       class="text-green-700 hover:underline dark:text-green-400">{{ $tenant->name }}</a>
                                    <span class="text-xs text-gray-400">scade il {{ $tenant->trial_ends_at->format('d/m/Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Licenze offline scadute --}}
                @if ($expiredLicenses->isNotEmpty())
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Licenze offline scadute</h3>
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                {{ $expiredLicenses->count() }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Il cliente è tornato al piano di base senza che nessuno l'abbia deciso.
                        </p>

                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($expiredLicenses as $tenant)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-3">
                                    <a href="{{ route('admin.tenants.show', $tenant) }}" wire:navigate
                                       class="text-green-700 hover:underline dark:text-green-400">{{ $tenant->name }}</a>
                                    <span class="text-xs text-gray-400">
                                        {{ $tenant->manual_plan }} · scaduta il {{ $tenant->manual_plan_expires_at?->format('d/m/Y') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
