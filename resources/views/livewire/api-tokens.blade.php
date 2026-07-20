<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">API & Integrazioni</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">
            Genera una chiave per collegare Replisa al tuo gestionale.
            <a href="{{ route('api.docs') }}" class="text-green-700 dark:text-green-500 underline font-semibold" wire:navigate>Guida all'API</a>.
        </p>

        {{-- Token appena creato: mostrato una sola volta --}}
        @if ($plainTextToken)
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                <p class="text-sm font-semibold text-green-800 dark:text-green-300">Ecco la tua chiave. Copiala adesso: non potrai più rivederla.</p>
                <div class="mt-2 flex items-center gap-2" x-data="{ copied: false }">
                    <code class="flex-1 text-xs bg-white dark:bg-gray-900 rounded px-3 py-2 break-all border border-green-200 dark:border-green-800">{{ $plainTextToken }}</code>
                    <button type="button"
                            x-on:click="navigator.clipboard.writeText('{{ $plainTextToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="shrink-0 px-3 py-2 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
                        <span x-show="!copied">Copia</span>
                        <span x-show="copied" x-cloak>Copiato ✓</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Genera una nuova chiave --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 mb-6">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Nuova chiave</h2>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                <div class="flex-1">
                    <label for="tokenName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                    <input id="tokenName" type="text" wire:model="name" wire:keydown.enter="create"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                           placeholder="es. Gestionale studio" />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="button" wire:click="create"
                        class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md transition-colors">
                    Genera chiave
                </button>
            </div>
        </div>

        {{-- Chiavi esistenti --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($tokens as $token)
                <div class="p-4 flex items-center justify-between" wire:key="token-{{ $token->id }}">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $token->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Creata {{ $token->created_at->translatedFormat('d M Y') }} ·
                            @if ($token->last_used_at)
                                usata l'ultima volta {{ $token->last_used_at->diffForHumans() }}
                            @else
                                mai usata
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="revoke({{ $token->id }})"
                            wire:confirm="Revocare questa chiave? Le integrazioni che la usano smetteranno di funzionare."
                            class="text-sm font-semibold text-red-600 hover:text-red-700">
                        Revoca
                    </button>
                </div>
            @empty
                <p class="p-4 text-sm text-gray-500 dark:text-gray-400">Nessuna chiave attiva.</p>
            @endforelse
        </div>
    </div>
</div>
