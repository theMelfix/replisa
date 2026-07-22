<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Log messaggi</h1>

        <div class="flex flex-col sm:flex-row gap-3 mb-3">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cerca contatto (nome o telefono)…"
                   class="flex-1 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            <select wire:model.live="direction" class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                <option value="">Tutte le direzioni</option>
                <option value="outbound">Inviati</option>
                <option value="inbound">Ricevuti</option>
            </select>
            <select wire:model.live="type" class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                <option value="">Tutti i tipi</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                <option value="">Tutti gli stati</option>
                @foreach (['queued','sent','delivered','read','failed','received'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
            <label class="flex items-center gap-2 text-sm text-gray-500">
                Dal
                <input type="date" wire:model.live="from" max="{{ $to ?: now()->format('Y-m-d') }}"
                       class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-500">
                al
                <input type="date" wire:model.live="to" min="{{ $from ?: '' }}"
                       class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            </label>
            @if ($search || $direction || $status || $type || $from || $to)
                <button type="button" wire:click="resetFilters"
                        class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    Azzera filtri
                </button>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Data</th>
                        <th class="px-4 py-3">Contatto</th>
                        <th class="px-4 py-3">Dir.</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Contenuto</th>
                        <th class="px-4 py-3">Stato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $m)
                        <tr class="border-b dark:border-gray-700" wire:key="msg-{{ $m->id }}">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $m->created_at->format('d/m H:i') }}</td>
                            <td class="px-4 py-3">{{ $m->contact?->name ?: $m->contact?->phone ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($m->direction === 'outbound')
                                    <span class="text-green-600">↑</span>
                                @else
                                    <span class="text-blue-600">↓</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $m->type }}</td>
                            <td class="px-4 py-3 max-w-xs truncate text-gray-700 dark:text-gray-300">
                                {{ $m->displayText() }}
                            </td>
                            <td class="px-4 py-3">
                                @php($colors = ['delivered'=>'bg-green-100 text-green-800','read'=>'bg-emerald-100 text-emerald-800','sent'=>'bg-blue-100 text-blue-800','failed'=>'bg-red-100 text-red-800','received'=>'bg-gray-100 text-gray-700','queued'=>'bg-amber-100 text-amber-800'])
                                <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $colors[$m->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $m->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nessun messaggio.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $messages->links() }}</div>
    </div>
</div>
