{{-- Configurazione del menu di benvenuto (E4.2.3) — vedi Automations::saveWelcomeSettings() --}}
<div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 space-y-4">
    <div>
        <label for="welcomeGreeting" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Messaggio di benvenuto</label>
        <textarea id="welcomeGreeting" wire:model="welcomeGreeting" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"></textarea>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">È la prima risposta che riceve chi ti scrive per la prima volta.</p>
        @error('welcomeGreeting')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="welcomeHeader" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titolo <span class="font-normal text-gray-400">(facoltativo)</span></label>
            <input id="welcomeHeader" type="text" wire:model="welcomeHeader" maxlength="60"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                   placeholder="es. il nome della tua attività" />
            @error('welcomeHeader')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="welcomeFooter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nota finale <span class="font-normal text-gray-400">(facoltativo)</span></label>
            <input id="welcomeFooter" type="text" wire:model="welcomeFooter" maxlength="60"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                   placeholder="es. Rispondiamo dal lunedì al venerdì" />
            @error('welcomeFooter')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bottoni del menu</p>
        <p class="mt-1 mb-2 text-xs text-gray-500 dark:text-gray-400">Fino a tre scelte. Lascia vuoto il titolo per non mostrare un bottone; la risposta è il messaggio inviato a chi lo tocca.</p>

        <div class="space-y-3">
            @foreach ($welcomeButtons as $i => $button)
                <div class="grid gap-2 sm:grid-cols-3" wire:key="welcome-button-{{ $i }}">
                    <input type="text" wire:model="welcomeButtons.{{ $i }}.title" maxlength="20"
                           class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                           placeholder="Titolo bottone {{ $i + 1 }}" aria-label="Titolo bottone {{ $i + 1 }}" />
                    <input type="text" wire:model="welcomeButtons.{{ $i }}.reply"
                           class="sm:col-span-2 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                           placeholder="Risposta automatica" aria-label="Risposta bottone {{ $i + 1 }}" />
                </div>
                @error('welcomeButtons.'.$i.'.title')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('welcomeButtons.'.$i.'.reply')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            @endforeach
        </div>
    </div>

    <button type="button" wire:click="saveWelcomeSettings"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md transition-colors">
        Salva
    </button>
</div>
