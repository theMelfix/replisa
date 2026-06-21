<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">Scadenze nazionali</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">Calendario condiviso con tutti i tenant. Aggiorna le date a ogni annuncio ufficiale.</p>

        {{-- Aggiungi --}}
        <form wire:submit="add" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 mb-8 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-48">
                <x-input-label for="name" value="Nome scadenza" />
                <x-text-input wire:model="name" id="name" type="text" class="block mt-1 w-full" placeholder="Es. IMU 2027 — acconto" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="due_date" value="Data" />
                <x-text-input wire:model="due_date" id="due_date" type="date" class="block mt-1" />
                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="sector" value="Settore" />
                <select wire:model="sector" id="sector" class="block mt-1 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                    <option value="">Tutti i settori</option>
                    @foreach ($sectors as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>Aggiungi</x-primary-button>
        </form>

        {{-- Elenco --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="py-3 px-4">Scadenza</th>
                        <th class="py-3 px-4">Settore</th>
                        <th class="py-3 px-4">Data</th>
                        <th class="py-3 px-4">Stato</th>
                        <th class="py-3 px-4">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deadlines as $deadline)
                        <tr class="border-b dark:border-gray-700" wire:key="nd-{{ $deadline->id }}">
                            <td class="py-3 px-4 font-medium">{{ $deadline->name }}</td>
                            <td class="py-3 px-4 text-xs text-gray-500">{{ $deadline->sector ? ($sectors[$deadline->sector] ?? $deadline->sector) : 'Tutti' }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <input type="date" wire:model="dates.{{ $deadline->id }}" class="text-xs rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 py-1">
                                    <button wire:click="updateDate({{ $deadline->id }})" class="text-xs text-green-700 hover:text-green-900">Salva</button>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if ($deadline->active)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Attiva</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-700">Disattiva</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <button wire:click="toggleActive({{ $deadline->id }})" class="text-gray-600 hover:text-gray-900 dark:text-gray-300">
                                    {{ $deadline->active ? 'Disattiva' : 'Attiva' }}
                                </button>
                                <button wire:click="delete({{ $deadline->id }})" wire:confirm="Eliminare la scadenza?" class="ml-3 text-gray-400 hover:text-red-600">Elimina</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-500">Nessuna scadenza nazionale.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
