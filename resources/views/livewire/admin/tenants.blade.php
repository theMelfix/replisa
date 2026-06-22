<div class="py-12" x-data="{ creating: false }" x-on:tenant-created.window="creating = false">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Clienti</h2>
                <button type="button" @click="creating = ! creating"
                        class="inline-flex items-center rounded-lg bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-700">
                    + Nuovo cliente
                </button>
            </div>
            <div class="text-right">
                <div class="text-xs uppercase text-gray-500">MRR stimato</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">€{{ number_format($mrr, 0, ',', '.') }}/mese</div>
            </div>
        </div>

        {{-- Form nuovo cliente (censimento da admin) --}}
        <div x-show="creating" x-cloak x-transition class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Nuovo cliente</h3>
            <form wire:submit="createTenant" class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="newBusinessName" value="Ragione sociale" />
                    <x-text-input wire:model="newBusinessName" id="newBusinessName" type="text" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('newBusinessName')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="newVatNumber" value="Partita IVA (opzionale)" />
                    <x-text-input wire:model="newVatNumber" id="newVatNumber" type="text" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('newVatNumber')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="newSector" value="Settore" />
                    <select wire:model="newSector" id="newSector" class="block mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                        <option value="">— seleziona —</option>
                        @foreach ($sectors as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('newSector')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="newOwnerName" value="Nome referente" />
                    <x-text-input wire:model="newOwnerName" id="newOwnerName" type="text" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('newOwnerName')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="newOwnerEmail" value="Email (accesso)" />
                    <x-text-input wire:model="newOwnerEmail" id="newOwnerEmail" type="email" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('newOwnerEmail')" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <x-input-label for="newPlan" value="Licenza offline" />
                        <select wire:model="newPlan" id="newPlan" class="block mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                            <option value="">— nessuna —</option>
                            @foreach ($plans as $key => $plan)
                                <option value="{{ $key }}">{{ $plan['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="newPlanExpiry" value="Scadenza" />
                        <x-text-input wire:model="newPlanExpiry" id="newPlanExpiry" type="date" class="block mt-1 w-full" />
                    </div>
                </div>
                <p class="sm:col-span-2 text-xs text-gray-500 dark:text-gray-400">
                    Il cliente riceverà un'email per impostare la password e attivare l'account.
                </p>
                <div class="sm:col-span-2 flex justify-end gap-3">
                    <button type="button" @click="creating = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Annulla</button>
                    <x-primary-button>Crea cliente</x-primary-button>
                </div>
            </form>
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
                        <th class="py-3 px-4">Licenza offline</th>
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
                                <div class="mt-2 flex items-center gap-1">
                                    <select wire:model="sectorInput.{{ $tenant->id }}" class="text-xs rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 py-1">
                                        <option value="">— settore —</option>
                                        @foreach ($sectors as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button wire:click="updateSector({{ $tenant->id }})" class="text-xs text-green-700 hover:text-green-900">Salva</button>
                                </div>
                            </td>

                            {{-- Piano effettivo + fonte --}}
                            <td class="py-3 px-4">
                                <div class="font-medium">{{ config('plans.plans.'.$limits->planKey().'.name') }}</div>
                                @if ($source === 'offline')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">Offline</span>
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

                            {{-- Licenza offline (colonna dedicata) --}}
                            <td class="py-3 px-4">
                                <div class="flex flex-col gap-1">
                                    <select wire:model="licensePlan.{{ $tenant->id }}" class="text-xs rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 py-1">
                                        <option value="">— piano —</option>
                                        @foreach ($plans as $key => $plan)
                                            <option value="{{ $key }}">{{ $plan['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" wire:model="licenseExpiry.{{ $tenant->id }}" class="text-xs rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 py-1">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="assignLicense({{ $tenant->id }})" class="text-xs font-medium text-green-700 hover:text-green-900">Assegna</button>
                                        @if ($tenant->manual_plan)
                                            <button wire:click="revokeLicense({{ $tenant->id }})" class="text-xs text-gray-500 hover:text-gray-700">Revoca</button>
                                            @if ($tenant->manual_plan_expires_at)
                                                <span class="text-xs text-gray-400">fino al {{ $tenant->manual_plan_expires_at->format('d/m/Y') }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Azioni --}}
                            <td class="py-3 px-4">
                                <div class="flex flex-col items-start gap-1 text-xs">
                                    <button wire:click="toggle({{ $tenant->id }})" class="font-medium {{ $tenant->active ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
                                        {{ $tenant->active ? 'Blocca' : 'Sblocca' }}
                                    </button>
                                    <button wire:click="cancelSubscription({{ $tenant->id }})" class="text-gray-600 hover:text-gray-900 dark:text-gray-300">Disdici Stripe</button>
                                    <button wire:click="refundLast({{ $tenant->id }})"
                                            wire:confirm="Rimborsare l'ultimo pagamento di {{ $tenant->name }}?"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-300">Rimborsa</button>
                                    <button wire:click="toggleReviewsAddon({{ $tenant->id }})" class="{{ $tenant->reviews_addon ? 'text-green-700 font-medium' : 'text-gray-600' }} hover:text-gray-900 dark:text-gray-300">
                                        Recensioni: {{ $tenant->reviews_addon ? 'ON' : 'OFF' }}
                                    </button>
                                </div>
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
