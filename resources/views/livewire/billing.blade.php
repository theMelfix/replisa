<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">Abbonamento</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">Scegli il piano per la tua attività. I costi di conversazione WhatsApp di Meta sono a parte.</p>

        @php
            $currentPlanKey = collect($plans)->search(fn ($p) => ! empty($p['stripe_price_id']) && $p['stripe_price_id'] === $currentPriceId);
        @endphp

        {{-- Stato abbonamento corrente --}}
        @if ($subscription && ($subscription->active() || $subscription->onGracePeriod()))
            <div class="mb-8 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">
                            Piano {{ $currentPlanKey ? $plans[$currentPlanKey]['name'] : 'attivo' }}
                        </h2>
                        @if ($subscription->onTrial())
                            <span class="text-xs font-semibold text-blue-700 bg-blue-100 rounded-full px-2 py-0.5">In prova</span>
                        @elseif ($subscription->onGracePeriod())
                            <span class="text-xs font-semibold text-amber-700 bg-amber-100 rounded-full px-2 py-0.5">In disdetta</span>
                        @else
                            <span class="text-xs font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">Attivo</span>
                        @endif
                    </div>
                    @if ($subscription->onGracePeriod() && $subscription->ends_at)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Attivo fino al {{ $subscription->ends_at->translatedFormat('d F Y') }}.</p>
                    @endif
                </div>
                <button type="button" wire:click="manageBilling"
                        class="inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Gestisci abbonamento
                </button>
            </div>
        @endif

        {{-- Utilizzo del mese corrente --}}
        @if ($usage)
            <div class="mb-8 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex items-baseline justify-between mb-4">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Utilizzo</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $usage['period_label'] }}</span>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($usage['sent'], 0, ',', '.') }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Messaggi inviati</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($usage['received'], 0, ',', '.') }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Messaggi ricevuti</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold {{ $contactsLimit && $usage['contacts'] > $contactsLimit ? 'text-amber-600' : 'text-gray-900 dark:text-gray-100' }}">
                            {{ number_format($usage['contacts'], 0, ',', '.') }}<span class="text-base font-medium text-gray-400">/{{ $contactsLimit ? number_format($contactsLimit, 0, ',', '.') : '∞' }}</span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contatti</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Griglia piani --}}
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($plans as $key => $plan)
                @php($isCurrent = $key === $currentPlanKey)
                <div class="rounded-2xl bg-white dark:bg-gray-800 border {{ $isCurrent ? 'border-green-500 ring-2 ring-green-500' : 'border-gray-200 dark:border-gray-700' }} p-6 flex flex-col" wire:key="plan-{{ $key }}">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $plan['name'] }}</h3>
                    <p class="mt-4">
                        <span class="text-4xl font-bold text-gray-900 dark:text-gray-100">€{{ $plan['price'] }}</span>
                        <span class="text-gray-500 dark:text-gray-400">/mese</span>
                    </p>

                    @if ($isCurrent)
                        <button type="button" disabled
                                class="mt-6 inline-flex justify-center rounded-lg bg-gray-100 dark:bg-gray-700 px-4 py-2 font-semibold text-gray-400 dark:text-gray-500 cursor-default">
                            Piano attuale
                        </button>
                    @else
                        <button type="button" wire:click="subscribe('{{ $key }}')" wire:loading.attr="disabled"
                                class="mt-6 inline-flex justify-center rounded-lg bg-green-600 px-4 py-2 font-semibold text-white hover:bg-green-700 disabled:opacity-60">
                            {{ $subscription && ($subscription->active() || $subscription->onGracePeriod()) ? 'Passa a ' . $plan['name'] : 'Attiva' }}
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">Pagamenti gestiti in modo sicuro da Stripe. Puoi disdire in qualsiasi momento dalla gestione abbonamento.</p>
    </div>
</div>
