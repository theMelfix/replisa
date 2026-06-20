<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-1">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Account WhatsApp</h1>
            @if ($isConnected)
                <span class="text-xs font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">Collegato</span>
            @else
                <span class="text-xs font-semibold text-gray-600 bg-gray-200 rounded-full px-2 py-0.5">Non collegato</span>
            @endif
        </div>
        <p class="text-gray-500 dark:text-gray-400 mb-8">
            Collega il numero WhatsApp Business della tua attività inserendo le credenziali della Meta Cloud API.
        </p>

        <form wire:submit="save" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-6">
            <div>
                <x-input-label for="phone_number_id" value="Phone Number ID" />
                <x-text-input wire:model="phone_number_id" id="phone_number_id" type="text" class="block mt-1 w-full" autocomplete="off" />
                <x-input-error :messages="$errors->get('phone_number_id')" class="mt-2" />
                <p class="mt-1 text-xs text-gray-400">Lo trovi nel pannello Meta &raquo; WhatsApp &raquo; Configurazione API.</p>
            </div>

            <div>
                <x-input-label for="waba_id" value="WhatsApp Business Account ID (WABA)" />
                <x-text-input wire:model="waba_id" id="waba_id" type="text" class="block mt-1 w-full" autocomplete="off" />
                <x-input-error :messages="$errors->get('waba_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="access_token" value="Access Token" />
                <x-text-input wire:model="access_token" id="access_token" type="password" class="block mt-1 w-full" autocomplete="off"
                              placeholder="{{ $isConnected ? '•••••••• (lascia vuoto per non cambiarlo)' : '' }}" />
                <x-input-error :messages="$errors->get('access_token')" class="mt-2" />
                <p class="mt-1 text-xs text-gray-400">Token permanente del System User. Viene salvato criptato e non viene mai più mostrato.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" wire:click="verify" wire:loading.attr="disabled"
                        class="inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-60">
                    Verifica connessione
                </button>
                <x-primary-button wire:loading.attr="disabled">
                    {{ __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
