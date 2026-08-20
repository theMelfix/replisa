<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Intestazione --}}
        <div>
            <a href="{{ route('admin.tenants') }}" wire:navigate
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Tutti i clienti
            </a>

            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $tenant->name }}</h1>

                @if ($tenant->active)
                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-500/15 dark:text-green-400">Attivo</span>
                @else
                    <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-500/15 dark:text-red-400">Bloccato</span>
                @endif

                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ $planName }}
                </span>
            </div>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Cliente dal {{ $tenant->created_at?->format('d/m/Y') }}
            </p>
        </div>

        {{-- Numeri in breve --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Contatti', $contactsCount],
                ['Messaggi', $messagesCount],
                ['Utenti', $users->count()],
                ['WhatsApp', $tenant->hasWhatsAppConfigured() ? 'Collegato' : 'Non collegato'],
            ] as [$label, $value])
                <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-1 text-xl font-bold {{ $label === 'WhatsApp' && ! $tenant->hasWhatsAppConfigured() ? 'text-amber-600' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ $value }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Anagrafica e dati fiscali --}}
        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">Anagrafica e dati fiscali</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                I campi fiscali sono facoltativi, ma per emettere fattura serve almeno
                <strong>codice SDI</strong> oppure <strong>PEC</strong>.
            </p>

            <form wire:submit="saveProfile" class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Ragione sociale" />
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="sector" value="Settore" />
                    <select wire:model="sector" id="sector"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">— Seleziona —</option>
                        @foreach ($sectors as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('sector')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="vat_number" value="Partita IVA" />
                    <x-text-input wire:model="vat_number" id="vat_number" type="text" class="mt-1 block w-full" inputmode="numeric" placeholder="11 cifre" />
                    <x-input-error :messages="$errors->get('vat_number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tax_code" value="Codice fiscale" />
                    <x-text-input wire:model="tax_code" id="tax_code" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('tax_code')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="address" value="Indirizzo" />
                    <x-text-input wire:model="address" id="address" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="city" value="Città" />
                    <x-text-input wire:model="city" id="city" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="postal_code" value="CAP" />
                        <x-text-input wire:model="postal_code" id="postal_code" type="text" class="mt-1 block w-full" inputmode="numeric" />
                        <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="province" value="Prov." />
                        <x-text-input wire:model="province" id="province" type="text" class="mt-1 block w-full uppercase" maxlength="2" />
                        <x-input-error :messages="$errors->get('province')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="country" value="Paese" />
                        <x-text-input wire:model="country" id="country" type="text" class="mt-1 block w-full uppercase" maxlength="2" />
                        <x-input-error :messages="$errors->get('country')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="sdi_code" value="Codice destinatario SDI" />
                    <x-text-input wire:model="sdi_code" id="sdi_code" type="text" class="mt-1 block w-full uppercase" maxlength="7" />
                    <x-input-error :messages="$errors->get('sdi_code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="pec" value="PEC" />
                    <x-text-input wire:model="pec" id="pec" type="email" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('pec')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <button type="submit"
                            class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                        Salva dati
                    </button>
                </div>
            </form>
        </div>

        {{-- Utenti e accessi --}}
        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">Utenti e accessi</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Chi può entrare nella dashboard di questo cliente. L'invito vale 7 giorni: se è scaduto, rispediscilo.
            </p>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase text-gray-500 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Ruolo</th>
                            <th class="px-4 py-3">Stato</th>
                            <th class="px-4 py-3">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                <td class="px-4 py-3">
                                    <a href="mailto:{{ $user->email }}" class="text-green-700 hover:underline dark:text-green-400">{{ $user->email }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $user->roles->pluck('name')->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->email_verified_at)
                                        <span class="text-green-700 dark:text-green-400">Attivo</span>
                                    @else
                                        <span class="text-amber-600">Invito in sospeso</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @unless ($user->email_verified_at)
                                        <button wire:click="resendInvitation({{ $user->id }})"
                                                wire:loading.attr="disabled"
                                                class="font-medium text-green-700 hover:text-green-900 disabled:opacity-50 dark:text-green-400">
                                            Rispedisci invito
                                        </button>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                    Nessun utente collegato a questo cliente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Collegamento WhatsApp --}}
        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">Collegamento WhatsApp</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Credenziali della WABA del cliente (ADR-003: una per cliente). Puoi inserirle tu durante
                l'onboarding assistito, oppure lasciarle fare a lui da <code>/whatsapp</code>.
            </p>

            <form wire:submit="saveWhatsApp" class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="phone_number_id" value="Phone Number ID" />
                    <x-text-input wire:model="phone_number_id" id="phone_number_id" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('phone_number_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="waba_id" value="WABA ID" />
                    <x-text-input wire:model="waba_id" id="waba_id" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('waba_id')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="access_token" value="Access Token" />
                    <x-text-input wire:model="access_token" id="access_token" type="password" class="mt-1 block w-full"
                                  autocomplete="new-password"
                                  placeholder="{{ $tenant->access_token ? 'Token salvato — lascia vuoto per non cambiarlo' : 'System User token permanente' }}" />
                    <x-input-error :messages="$errors->get('access_token')" class="mt-2" />
                    <p class="mt-2 text-xs text-gray-500">
                        Il token è criptato a riposo e non viene mai ri-mostrato.
                    </p>
                </div>

                <div class="flex gap-3 sm:col-span-2">
                    <button type="submit"
                            class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                        Salva credenziali
                    </button>
                    <button type="button" wire:click="verifyWhatsApp" wire:loading.attr="disabled"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Verifica connessione
                    </button>
                </div>
            </form>
        </div>

        {{-- Abbonamento --}}
        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">Abbonamento e piano</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Blocco, licenze offline, add-on, disdetta e rimborso restano nell'<a href="{{ route('admin.tenants') }}" wire:navigate class="text-green-700 underline dark:text-green-400">elenco clienti</a>.
            </p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Piano attivo</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">
                        {{ $planName }}
                        <span class="text-sm text-gray-500">({{ [
                            'offline' => 'licenza offline',
                            'trial' => 'prova gratuita',
                            'stripe' => 'abbonamento Stripe',
                            'default' => 'piano di base',
                        ][$planSource] ?? $planSource }})</span>
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Add-on Recensioni</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $tenant->reviews_addon ? 'Attivo' : 'Non attivo' }}</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Prova gratuita</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">
                        {{ $tenant->trial_ends_at ? 'fino al '.$tenant->trial_ends_at->format('d/m/Y') : '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Licenza offline</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">
                        @if ($tenant->manual_plan)
                            {{ $tenant->manual_plan }}
                            @if ($tenant->manual_plan_expires_at)
                                <span class="text-sm text-gray-500">fino al {{ $tenant->manual_plan_expires_at->format('d/m/Y') }}</span>
                            @endif
                            @unless ($tenant->hasActiveOfflineLicense())
                                <span class="text-sm text-red-600">(scaduta)</span>
                            @endunless
                        @else
                            —
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Stato Stripe</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $subscriptionStatus ?? '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Fine abbonamento</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">
                        {{ $subscriptionEndsAt?->format('d/m/Y') ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
