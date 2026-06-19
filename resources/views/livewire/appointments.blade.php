<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Appuntamenti</h1>

        <div class="grid gap-6 lg:grid-cols-3 mb-8">
            {{-- Nuovo appuntamento --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Nuovo appuntamento</h2>
                <form wire:submit="create" class="grid gap-3 sm:grid-cols-4 items-end">
                    <div class="sm:col-span-1">
                        <label class="block text-xs text-gray-500 mb-1">Telefono</label>
                        <input type="text" wire:model="phone" placeholder="3934..." class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                        @error('phone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs text-gray-500 mb-1">Nome</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs text-gray-500 mb-1">Data e ora</label>
                        <input type="datetime-local" wire:model="scheduled_at" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                        @error('scheduled_at') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="sm:col-span-1">
                        <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2 text-white text-sm font-semibold hover:bg-green-700">Aggiungi</button>
                    </div>
                </form>
            </div>

            {{-- Import CSV --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Import CSV</h2>
                <p class="text-xs text-gray-500 mb-3">Colonne: <code>telefono, nome, data ora</code></p>
                <form wire:submit="import" class="space-y-3">
                    <input type="file" wire:model="csv" accept=".csv,.txt" class="block w-full text-sm text-gray-600">
                    @error('csv') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600">Importa</button>
                    <span wire:loading wire:target="import" class="text-xs text-gray-500">Importazione…</span>
                </form>
                @if ($importMessage)
                    <p class="mt-3 text-sm text-green-700">{{ $importMessage }}</p>
                @endif
            </div>
        </div>

        {{-- Lista --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Quando</th>
                        <th class="px-4 py-3">Contatto</th>
                        <th class="px-4 py-3">Stato</th>
                        <th class="px-4 py-3">Reminder</th>
                        <th class="px-4 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $a)
                        <tr class="border-b dark:border-gray-700" wire:key="appt-{{ $a->id }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $a->scheduled_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $a->contact?->name ?: $a->contact?->phone ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @php($colors = ['scheduled'=>'bg-blue-100 text-blue-800','confirmed'=>'bg-green-100 text-green-800','cancelled'=>'bg-red-100 text-red-800','completed'=>'bg-gray-200 text-gray-700'])
                                <select wire:change="updateStatus({{ $a->id }}, $event.target.value)"
                                        class="text-xs rounded-full border-0 {{ $colors[$a->status] ?? '' }} py-0.5 pl-2 pr-7">
                                    @foreach (['scheduled'=>'Programmato','confirmed'=>'Confermato','cancelled'=>'Disdetto','completed'=>'Completato'] as $val => $lbl)
                                        <option value="{{ $val }}" @selected($a->status === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $a->reminded_at ? '✓ '.$a->reminded_at->format('d/m H:i') : '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="delete({{ $a->id }})" wire:confirm="Eliminare l'appuntamento?" class="text-red-600 hover:text-red-800 text-xs">Elimina</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nessun appuntamento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $appointments->links() }}</div>
    </div>
</div>
