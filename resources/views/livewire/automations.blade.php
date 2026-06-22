<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">Automazioni</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">Attiva o disattiva i flussi per la tua attività.</p>

        <div class="space-y-4">
            @foreach ($flows as $type => $flow)
                @php($isActive = (bool) ($active[$type] ?? false))
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 flex items-center justify-between" wire:key="flow-{{ $type }}">
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
            @endforeach
        </div>

        {{-- Add-on Recensioni --}}
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-10 mb-3">Add-on</h2>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 flex items-center justify-between">
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
    </div>
</div>
