<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">Campagne e comunicazioni</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">Invia un messaggio a tutti i contatti con consenso (opt-in) tramite un template approvato.</p>

        @unless ($allowed)
            <div class="mb-6 rounded-lg bg-amber-50 ring-1 ring-amber-200 px-4 py-3 text-sm text-amber-800">
                Le campagne sono incluse dal piano <strong>Base</strong> in su.
                <a href="{{ route('billing') }}" class="underline font-semibold" wire:navigate>Aggiorna il piano</a> per inviarle.
            </div>
        @endunless

        {{-- Compositore --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 mb-8">
            <form wire:submit="send" class="space-y-5">
                <div>
                    <x-input-label for="name" value="Nome campagna (interno)" />
                    <x-text-input wire:model="name" id="name" type="text" class="block mt-1 w-full" placeholder="Es. Promo IMU giugno" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="template_name" value="Nome template Meta (approvato)" />
                        <x-text-input wire:model="template_name" id="template_name" type="text" class="block mt-1 w-full" placeholder="es. promo_scadenza" />
                        <x-input-error :messages="$errors->get('template_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="language" value="Lingua" />
                        <x-text-input wire:model="language" id="language" type="text" class="block mt-1 w-full" maxlength="5" />
                        <x-input-error :messages="$errors->get('language')" class="mt-2" />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model="include_name" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                    Personalizza con il nome del contatto (parametro @{{1}} del template)
                </label>

                <div class="flex items-center justify-between pt-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Destinatari opt-in: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($optedInCount, 0, ',', '.') }}</span>
                    </span>
                    <x-primary-button wire:loading.attr="disabled" wire:confirm="Inviare la campagna a {{ $optedInCount }} contatti?">
                        Invia campagna
                    </x-primary-button>
                </div>
            </form>
        </div>

        {{-- Storico campagne --}}
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Campagne inviate</h2>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="py-3 px-4">Campagna</th>
                        <th class="py-3 px-4">Template</th>
                        <th class="py-3 px-4">Stato</th>
                        <th class="py-3 px-4 text-right">Inviati</th>
                        <th class="py-3 px-4 text-right">Falliti</th>
                        <th class="py-3 px-4">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr class="border-b dark:border-gray-700" wire:key="campaign-{{ $campaign->id }}">
                            <td class="py-3 px-4 font-medium">{{ $campaign->name }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $campaign->template_name }}</td>
                            <td class="py-3 px-4">
                                @php($map = [
                                    'pending' => ['In coda', 'bg-gray-200 text-gray-700'],
                                    'sending' => ['In invio', 'bg-blue-100 text-blue-800'],
                                    'completed' => ['Completata', 'bg-green-100 text-green-800'],
                                    'failed' => ['Fallita', 'bg-red-100 text-red-800'],
                                ])
                                @php($badge = $map[$campaign->status] ?? [$campaign->status, 'bg-gray-200 text-gray-700'])
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $badge[1] }}">{{ $badge[0] }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">{{ $campaign->sent_count }}/{{ $campaign->total }}</td>
                            <td class="py-3 px-4 text-right">{{ $campaign->failed_count }}</td>
                            <td class="py-3 px-4 text-gray-500">{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-gray-500">Nessuna campagna inviata.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $campaigns->links() }}</div>
    </div>
</div>
