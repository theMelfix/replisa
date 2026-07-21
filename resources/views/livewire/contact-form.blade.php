<div>
    @if ($sent)
        <div class="rounded-2xl border border-green-200 bg-green-50 p-8 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-2xl">✓</div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">Richiesta inviata!</h3>
            <p class="mt-2 text-gray-600">Ti ricontattiamo entro un giorno lavorativo per fissare la demo.</p>
        </div>
    @else
        <form wire:submit="submit" class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 text-left shadow-sm">
            {{-- Honeypot: nascosto agli utenti, i bot lo compilano --}}
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label>Sito web<input type="text" wire:model="website" tabindex="-1" autocomplete="off" /></label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="cf-name" class="block text-sm font-medium text-gray-700">Nome *</label>
                    <input id="cf-name" type="text" wire:model="name"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cf-email" class="block text-sm font-medium text-gray-700">Email *</label>
                    <input id="cf-email" type="email" wire:model="email"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cf-phone" class="block text-sm font-medium text-gray-700">Telefono</label>
                    <input id="cf-phone" type="tel" wire:model="phone"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cf-business" class="block text-sm font-medium text-gray-700">Attività</label>
                    <input id="cf-business" type="text" wire:model="business" placeholder="es. Studio dentistico"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                    @error('business') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mt-4">
                <label for="cf-message" class="block text-sm font-medium text-gray-700">Messaggio</label>
                <textarea id="cf-message" wire:model="message" rows="3" placeholder="Raccontaci cosa vorresti automatizzare"
                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex items-center justify-between gap-4">
                <p class="text-xs text-gray-500">Inviando accetti la <a href="/privacy" class="underline hover:text-gray-700">privacy policy</a>.</p>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 font-semibold text-white hover:bg-green-700 disabled:opacity-60"
                        wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">Prenota una demo</span>
                    <span wire:loading wire:target="submit">Invio…</span>
                </button>
            </div>
        </form>
    @endif
</div>
