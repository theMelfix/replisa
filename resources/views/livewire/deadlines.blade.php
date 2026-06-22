<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">Scadenze</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">Attiva un promemoria automatico ai tuoi contatti (opt-in) prima di una scadenza nazionale o di una tua.</p>

        @unless ($allowed)
            <div class="mb-6 rounded-lg bg-amber-50 ring-1 ring-amber-200 px-4 py-3 text-sm text-amber-800">
                I promemoria scadenze sono inclusi dal piano <strong>Base</strong> in su.
                <a href="{{ route('billing') }}" class="underline font-semibold" wire:navigate>Aggiorna il piano</a> per attivarli.
            </div>
        @endunless

        {{-- Aggiungi scadenza propria --}}
        <form wire:submit="addDeadline" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 mb-8 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-48">
                <x-input-label for="newName" value="Nuova scadenza (tua)" />
                <x-text-input wire:model="newName" id="newName" type="text" class="block mt-1 w-full" placeholder="Es. Rinnovo polizza" />
                <x-input-error :messages="$errors->get('newName')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="newDate" value="Data" />
                <x-text-input wire:model="newDate" id="newDate" type="date" class="block mt-1" />
                <x-input-error :messages="$errors->get('newDate')" class="mt-2" />
            </div>
            <x-primary-button>Aggiungi</x-primary-button>
        </form>

        {{-- Elenco scadenze + promemoria --}}
        <div class="space-y-4">
            @forelse ($deadlines as $deadline)
                @php($reminder = $reminders[$deadline->id] ?? null)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6" wire:key="deadline-{{ $deadline->id }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $deadline->name }}</h2>
                                @if ($deadline->isNational())
                                    <span class="text-xs font-semibold text-blue-700 bg-blue-100 rounded-full px-2 py-0.5">Nazionale</span>
                                @else
                                    <span class="text-xs font-semibold text-gray-600 bg-gray-200 rounded-full px-2 py-0.5">Tua</span>
                                @endif
                                @if ($reminder && $reminder->active)
                                    <span class="text-xs font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">Promemoria attivo</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Scadenza: {{ $deadline->due_date->format('d/m/Y') }}</p>
                        </div>
                        @unless ($deadline->isNational())
                            <button wire:click="deleteDeadline({{ $deadline->id }})" wire:confirm="Eliminare questa scadenza?" class="text-xs text-gray-400 hover:text-red-600">Elimina</button>
                        @endunless
                    </div>

                    <div class="mt-4 grid sm:grid-cols-[1fr_auto_auto] gap-3 items-end">
                        <div>
                            <x-input-label :for="'tpl-'.$deadline->id" value="Template Meta (approvato)" />
                            <x-text-input wire:model="reminderTemplate.{{ $deadline->id }}" :id="'tpl-'.$deadline->id" type="text" class="block mt-1 w-full text-sm" placeholder="es. promemoria_scadenza" />
                        </div>
                        <div>
                            <x-input-label :for="'days-'.$deadline->id" value="Giorni prima" />
                            <x-text-input wire:model="reminderDays.{{ $deadline->id }}" :id="'days-'.$deadline->id" type="number" min="1" max="365" class="block mt-1 w-24 text-sm" />
                        </div>
                        <div class="flex items-center gap-3">
                            <button wire:click="saveReminder({{ $deadline->id }})" class="inline-flex justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                {{ $reminder ? 'Salva' : 'Attiva' }}
                            </button>
                            @if ($reminder)
                                <button wire:click="toggleReminder({{ $deadline->id }})" class="text-xs text-gray-500 hover:text-gray-700">
                                    {{ $reminder->active ? 'Disattiva' : 'Riattiva' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 py-8">Nessuna scadenza in arrivo.</p>
            @endforelse
        </div>
    </div>
</div>
