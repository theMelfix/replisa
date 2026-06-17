<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-lg font-semibold mb-4">{{ __('Tenant') }}</h2>

                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                        <tr>
                            <th class="py-2 pr-4">Nome</th>
                            <th class="py-2 pr-4">Piano</th>
                            <th class="py-2 pr-4">Phone Number ID</th>
                            <th class="py-2 pr-4 text-right">Contatti</th>
                            <th class="py-2 pr-4 text-right">Messaggi</th>
                            <th class="py-2 pr-4">Stato</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            <tr class="border-b dark:border-gray-700" wire:key="tenant-{{ $tenant->id }}">
                                <td class="py-2 pr-4 font-medium">{{ $tenant->name }}</td>
                                <td class="py-2 pr-4">{{ $tenant->plan ?? '—' }}</td>
                                <td class="py-2 pr-4 font-mono text-xs">{{ $tenant->phone_number_id ?? '—' }}</td>
                                <td class="py-2 pr-4 text-right">{{ $tenant->contacts_count }}</td>
                                <td class="py-2 pr-4 text-right">{{ $tenant->messages_count }}</td>
                                <td class="py-2 pr-4">
                                    @if ($tenant->active)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Attivo</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-700">Disattivo</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4">
                                    <button wire:click="toggle({{ $tenant->id }})"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                                        {{ $tenant->active ? 'Disattiva' : 'Attiva' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">Nessun tenant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
