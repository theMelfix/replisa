<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Richieste demo</h1>

        @php($labels = ['new' => 'Nuovo', 'contacted' => 'Contattato', 'converted' => 'Convertito', 'archived' => 'Archiviato'])
        @php($badges = ['new' => 'bg-amber-100 text-amber-800', 'contacted' => 'bg-blue-100 text-blue-800', 'converted' => 'bg-green-100 text-green-800', 'archived' => 'bg-gray-100 text-gray-600'])

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cerca nome, email o attività…"
                   class="flex-1 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            <select wire:model.live="status" class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                <option value="">Tutti gli stati</option>
                @foreach ($labels as $key => $label)
                    <option value="{{ $key }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Data</th>
                        <th class="px-4 py-3">Contatto</th>
                        <th class="px-4 py-3">Attività</th>
                        <th class="px-4 py-3">Messaggio</th>
                        <th class="px-4 py-3">Stato</th>
                        <th class="px-4 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr class="border-b dark:border-gray-700 align-top" wire:key="lead-{{ $lead->id }}">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $lead->created_at->format('d/m H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $lead->name }}</div>
                                <a href="mailto:{{ $lead->email }}" class="text-green-700 dark:text-green-500 hover:underline">{{ $lead->email }}</a>
                                @if ($lead->phone)
                                    <div class="text-gray-500">{{ $lead->phone }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $lead->business ?: '—' }}</td>
                            <td class="px-4 py-3 max-w-xs text-gray-700 dark:text-gray-300">
                                <span class="line-clamp-3">{{ $lead->message ?: '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $badges[$lead->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $labels[$lead->status] ?? $lead->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <select wire:change="setStatus({{ $lead->id }}, $event.target.value)"
                                            class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-xs py-1">
                                        @foreach ($labels as $key => $label)
                                            <option value="{{ $key }}" @selected($lead->status === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="delete({{ $lead->id }})"
                                            wire:confirm="Eliminare questo lead?"
                                            class="text-red-600 hover:text-red-700 text-xs font-semibold">Elimina</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nessuna richiesta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $leads->links() }}</div>
    </div>
</div>
