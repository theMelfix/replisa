<div>
    @if ($used)
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Questo invito è già stato utilizzato.
            <a href="{{ route('login') }}" class="underline hover:text-gray-900 dark:hover:text-gray-100" wire:navigate>Vai al login</a>.
        </div>
    @else
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Ciao <strong>{{ $user->name }}</strong>, imposta una password per attivare l'account
            <strong>{{ $user->tenant?->name }}</strong>.
        </div>

        <form wire:submit="activate">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-6">
                <x-primary-button>Attiva account</x-primary-button>
            </div>
        </form>
    @endif
</div>
