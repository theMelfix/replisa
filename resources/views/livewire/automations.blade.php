<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">Automazioni</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">Attiva o disattiva i flussi per la tua attività.</p>

        <div class="space-y-4">
            @foreach ($flows as $type => $flow)
                @php($isActive = (bool) ($active[$type] ?? false))
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6" wire:key="flow-{{ $type }}">
                    <div class="flex items-center justify-between">
                        <div class="pr-6">
                            <div class="flex items-center gap-2">
                                <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $flow['label'] }}</h2>
                                @if ($isActive)
                                    <span class="text-xs font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">Attivo</span>
                                @else
                                    <span class="text-xs font-semibold text-gray-600 bg-gray-200 rounded-full px-2 py-0.5">Disattivo</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $flow['desc'] }}</p>
                        </div>

                        <button type="button" wire:click="toggle('{{ $type }}')"
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors {{ $isActive ? 'bg-green-600' : 'bg-gray-300' }}"
                                role="switch" aria-checked="{{ $isActive ? 'true' : 'false' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $isActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>

                    @if ($isActive && $type === \App\Models\Automation::TYPE_WELCOME)
                        @include('livewire.partials.welcome-settings')
                    @elseif ($isActive && $type === \App\Models\Automation::TYPE_APPOINTMENT_REMINDER)
                        @include('livewire.partials.reminder-settings')
                    @elseif ($isActive && $type === \App\Models\Automation::TYPE_CAMPAIGN)
                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Le campagne si compongono e si inviano dalla sezione
                                <a href="{{ route('campaigns') }}" class="text-green-700 dark:text-green-500 underline font-semibold" wire:navigate>Campagne</a>.
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Add-on Recensioni --}}
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-10 mb-3">Add-on</h2>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div class="pr-6">
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Richiesta recensione</h2>
                        @if ($hasReviews && $reviewActive)
                            <span class="text-xs font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">Attivo</span>
                        @elseif (! $hasReviews)
                            <span class="text-xs font-semibold text-amber-700 bg-amber-100 rounded-full px-2 py-0.5">Add-on</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Richiesta recensione Google dopo un appuntamento completato.</p>
                    @unless ($hasReviews)
                        <p class="mt-1 text-xs text-amber-700">Incluso nel piano Business o come add-on. <a href="{{ route('billing') }}" class="underline font-semibold" wire:navigate>Attiva</a>.</p>
                    @endunless
                </div>

                @if ($hasReviews)
                    <button type="button" wire:click="toggleReviews"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors {{ $reviewActive ? 'bg-green-600' : 'bg-gray-300' }}"
                            role="switch" aria-checked="{{ $reviewActive ? 'true' : 'false' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $reviewActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                @else
                    <span class="text-gray-300" title="Add-on non attivo">🔒</span>
                @endif
            </div>

            {{-- Configurazione link recensione (guardrail statico/dinamico) --}}
            @if ($hasReviews && $reviewActive)
                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 space-y-4">
                    <div>
                        <label for="reviewUrlMode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Link recensione nel template</label>
                        <select id="reviewUrlMode" wire:model.live="reviewUrlMode"
                                class="mt-1 block w-full sm:max-w-md rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <option value="static">Il link è già scritto nel template (consigliato)</option>
                            <option value="dynamic">Il template usa un link dinamico (@{{1}})</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Scegli "dinamico" solo se il bottone del tuo template Meta contiene un segnaposto variabile nell'URL.</p>
                    </div>

                    @if ($reviewUrlMode === 'dynamic')
                        <div>
                            <label for="reviewUrlParam" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suffisso link</label>
                            <input id="reviewUrlParam" type="text" wire:model="reviewUrlParam"
                                   class="mt-1 block w-full sm:max-w-md rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                   placeholder="es. il place id Google" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">È solo la parte variabile appesa alla base URL del bottone del template, non un indirizzo completo.</p>
                            @error('reviewUrlParam')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <button type="button" wire:click="saveReviewSettings"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md transition-colors">
                        Salva
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
