<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Contatti</h1>

        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cerca per nome o telefono…"
               class="w-full sm:w-96 mb-4 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Telefono</th>
                        <th class="px-4 py-3">Opt-in</th>
                        <th class="px-4 py-3">Ultimo contatto</th>
                        <th class="px-4 py-3 text-right">Messaggi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $c)
                        <tr class="border-b dark:border-gray-700" wire:key="contact-{{ $c->id }}">
                            <td class="px-4 py-3 font-medium">{{ $c->name ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $c->phone }}</td>
                            <td class="px-4 py-3">
                                @if ($c->opted_in)
                                    <span class="text-xs font-medium rounded-full px-2 py-0.5 bg-green-100 text-green-800">Sì</span>
                                @else
                                    <span class="text-xs font-medium rounded-full px-2 py-0.5 bg-gray-100 text-gray-600">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $c->last_seen_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $c->messages_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nessun contatto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $contacts->links() }}</div>
    </div>
</div>
