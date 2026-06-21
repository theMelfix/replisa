<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Clienti</h2>
            <div class="text-right">
                <div class="text-xs uppercase text-gray-500">MRR stimato</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">€{{ number_format($mrr, 0, ',', '.') }}/mese</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-x-auto shadow-sm sm:rounded-lg">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="py-3 px-4">Attività</th>
                        <th class="py-3 px-4">Piano</th>
                        <th class="py-3 px-4 text-right">Contatti</th>
                        <th class="py-3 px-4 text-right">Msg</th>
                        <th class="py-3 px-4">Stato</th>
                        <th class="py-3 px-4">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenants as $tenant)
                        @php($limits = \App\Support\PlanLimits::for($tenant))
                        @php($source = $limits->planSource())
                        <tr class="border-b dark:border-gray-700 align-top" wire:key="tenant-{{ $tenant->id }}">
                            {{-- Attività + dati fiscali --}}
                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $tenant->name }}</div>
                                @if ($tenant->vat_number)
                                    <div class="text-xs text-gray-500 flex items-center gap-1">
                                        P.IVA {{ $tenant->vat_number }}
                                        @if ($tenant->vat_validated_at)
                                            <span class="text-green-600" title="Validata su VIES">✓</span>
                                        @else
                                            <span class="text-amber-600" title="Da verificare">⏳</span>
                                        @endif
                                    </div>
                                @endif
                                @if ($tenant->city)
                                    <div class="text-xs text-gray-400">{{ $tenant->city }} {{ $tenant->province ? '('.$tenant->province.')' : '' }}</div>
                                @endif
                            </td>

                            {{-- Piano effettivo + fonte --}}
                            <td class="py-3 px-4">
                                <div class="font-medium">{{ config('plans.plans.'.$limits->planKey().'.name') }}</div>
                                @if ($source === 'offline')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">Offline</span>
                                    @if ($tenant->manual_plan_expires_at)
                                        <div class="text-xs text-gray-400 mt-0.5">fino al {{ $tenant->manual_plan_expires_at->format('d/m/Y') }}</div>
                                    @endif
                                @elseif ($source === 'stripe')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-800">Stripe</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-700">Free</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right">{{ $tenant->contacts_count }}</td>
                            <td class="py-3 px-4 text-right">{{ $tenant->messages_count }}</td>

                            {{-- Stato --}}
                            <td class="py-3 px-4">
                                @if ($tenant->active)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Attivo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-800">Bloccato</span>
                                @endif
                            </td>

                            {{-- Azioni --}}
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                    <button wire:click="toggle({{ $tenant->id }})" class="font-medium {{ $tenant->active ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
                                        {{ $tenant->active ? 'Blocca' : 'Sblocca' }}
                                    </button>
                                    <button wire:click="cancelSubscription({{ $tenant->id }})" class="text-gray-600 hover:text-gray-900 dark:text-gray-300">Disdici</button>
                                    <button wire:click="refundLast({{ $tenant->id }})"
                                            wire:confirm="Rimborsare l'ultimo pagamento di {{ $tenant->name }}?"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-300">Rimborsa</button>
                                </div>

                                {{-- Licenza offline --}}
                                <div class="flex flex-wrap items-center gap-1 mt-2">
                                    <select wire:model="licensePlan.{{ $tenant->id }}" class="text-xs rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 py-1">
                                        <option value="">— licenza offline —</option>
                                        @foreach ($plans as $key => $plan)
                                            <option value="{{ $key }}">{{ $plan['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" wire:model="licenseExpiry.{{ $tenant->id }}" class="text-xs rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 py-1">
                                    <button wire:click="assignLicense({{ $tenant->id }})" class="text-xs font-medium text-green-700 hover:text-green-900">Assegna</button>
                                    @if ($tenant->manual_plan)
                                        <button wire:click="revokeLicense({{ $tenant->id }})" class="text-xs text-gray-500 hover:text-gray-700">Revoca</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-500">Nessun tenant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
