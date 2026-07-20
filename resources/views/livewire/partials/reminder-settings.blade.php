{{-- Configurazione dei promemoria (E4.2.3) — vedi Automations::saveReminderSettings() --}}
<div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 space-y-4">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="reminderTemplate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Meta</label>
            <input id="reminderTemplate" type="text" wire:model="reminderTemplate"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nome esatto del modello approvato in WhatsApp Manager, con nome/data/ora come variabili.</p>
            @error('reminderTemplate')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="reminderLanguage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lingua</label>
            <input id="reminderLanguage" type="text" wire:model="reminderLanguage"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                   placeholder="it" />
            @error('reminderLanguage')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="reminderOffsets" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quanto tempo prima</label>
        <input id="reminderOffsets" type="text" wire:model="reminderOffsets"
               class="mt-1 block w-full sm:max-w-md rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
               placeholder="24, 2" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ore di anticipo, separate da virgola. Con "24, 2" il cliente riceve un promemoria il giorno prima e uno due ore prima.</p>
        @error('reminderOffsets')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="reminderConfirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bottone di conferma</label>
            <input id="reminderConfirm" type="text" wire:model="reminderConfirm" maxlength="25"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" />
            @error('reminderConfirm')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="reminderCancel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bottone di disdetta</label>
            <input id="reminderCancel" type="text" wire:model="reminderCancel" maxlength="25"
                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" />
            @error('reminderCancel')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        Devono coincidere <strong>parola per parola</strong> con i bottoni del template approvato: WhatsApp rimanda indietro il testo del bottone, ed è così che l'appuntamento viene segnato confermato o disdetto.
    </p>

    <button type="button" wire:click="saveReminderSettings"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md transition-colors">
        Salva
    </button>
</div>
