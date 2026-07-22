<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">
            Ciao {{ auth()->user()->name }} 👋
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">{{ auth()->user()->tenant?->name }}</p>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                ['Messaggi inviati', $inviati, 'text-green-600'],
                ['Messaggi ricevuti', $ricevuti, 'text-blue-600'],
                ['Contatti', $contatti, 'text-gray-800 dark:text-gray-100'],
                ['Conversazioni attive', $conversazioniAttive, 'text-amber-600'],
                ['Appuntamenti in arrivo', $appuntamentiProssimi, 'text-purple-600'],
            ] as [$label, $value, $color])
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Grafico andamento messaggi --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Andamento messaggi</h2>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-green-500"></span>Inviati</span>
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-blue-400"></span>Ricevuti</span>
                    </div>
                </div>

                @if ($trendMax === 0)
                    <p class="text-sm text-gray-500 py-12 text-center">Nessun messaggio negli ultimi {{ \App\Livewire\Dashboard::TREND_DAYS }} giorni.</p>
                @else
                    <div class="flex items-end justify-between gap-1 h-40">
                        @foreach ($trend as $day)
                            <div class="flex-1 flex flex-col items-center gap-1 group relative">
                                <div class="flex items-end gap-0.5 h-32 w-full justify-center">
                                    {{-- Altezza in % del massimo; min 2px per rendere visibili i valori piccoli --}}
                                    <div class="w-2 rounded-t bg-green-500"
                                         style="height: {{ $day['inviati'] > 0 ? max(4, round($day['inviati'] / $trendMax * 100)) : 0 }}%"></div>
                                    <div class="w-2 rounded-t bg-blue-400"
                                         style="height: {{ $day['ricevuti'] > 0 ? max(4, round($day['ricevuti'] / $trendMax * 100)) : 0 }}%"></div>
                                </div>
                                <span class="text-[10px] text-gray-400">{{ $day['label'] }}</span>

                                {{-- Tooltip --}}
                                <div class="pointer-events-none absolute bottom-full mb-1 hidden group-hover:block z-10 whitespace-nowrap rounded bg-gray-900 text-white text-[10px] px-2 py-1">
                                    {{ $day['label'] }}: {{ $day['inviati'] }} inviati, {{ $day['ricevuti'] }} ricevuti
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Costo Meta stimato --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 flex flex-col">
                <div class="flex items-center gap-2">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Costo Meta stimato</h2>
                    <span class="text-xs text-gray-400" title="Stima basata sui template inviati questo mese. La fatturazione reale la emette Meta.">ⓘ</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Mese corrente</p>

                <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-gray-100">
                    € {{ number_format($costoStimato, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $templateMese }} {{ $templateMese === 1 ? 'conversazione a pagamento' : 'conversazioni a pagamento' }}
                </p>

                <p class="mt-auto pt-4 text-xs text-gray-400 leading-relaxed">
                    Stima orientativa: ogni template inviato apre una conversazione fatturabile.
                    I costi effettivi sono addebitati da Meta secondo il suo listino.
                </p>
            </div>
        </div>

        <div class="mt-8 flex gap-3">
            <a href="{{ route('automations') }}" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-white text-sm font-semibold hover:bg-green-700">Gestisci automazioni</a>
        </div>
    </div>
</div>
