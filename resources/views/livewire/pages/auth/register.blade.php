<?php

use App\Models\Tenant;
use App\Models\User;
use App\Rules\ItalianVatChecksum;
use App\Services\Vies\ViesClient;
use App\Support\ItalianVat;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $business_name = '';
    public string $vat_number = '';
    public string $tax_code = '';
    public string $address = '';
    public string $city = '';
    public string $postal_code = '';
    public string $province = '';
    public string $sdi_code = '';
    public string $pec = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Self-service signup con dati fiscali (E6.2/anti-spam): crea il tenant
     * (l'attività) e l'utente che lo registra ne diventa owner. La P.IVA è
     * validata via checksum + VIES; le credenziali WhatsApp si configurano dopo.
     */
    public function register(): void
    {
        $this->vat_number = ItalianVat::normalize($this->vat_number);
        $this->province = strtoupper(trim($this->province));
        $this->sdi_code = strtoupper(trim($this->sdi_code));

        $validated = $this->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['required', 'string', new ItalianVatChecksum, 'unique:tenants,vat_number'],
            'tax_code' => ['nullable', 'string', 'max:16'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'regex:/^\d{5}$/'],
            'province' => ['required', 'regex:/^[A-Z]{2}$/'],
            'sdi_code' => ['nullable', 'required_without:pec', 'string', 'regex:/^[A-Z0-9]{6,7}$/'],
            'pec' => ['nullable', 'required_without:sdi_code', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], attributes: [
            'business_name' => 'ragione sociale',
            'vat_number' => 'Partita IVA',
            'tax_code' => 'codice fiscale',
            'postal_code' => 'CAP',
            'province' => 'provincia',
            'sdi_code' => 'codice destinatario SDI',
        ]);

        // Verifica VIES: blocca solo se la P.IVA risulta esplicitamente
        // inesistente/non attiva. Se VIES è irraggiungibile (null) si procede
        // lasciando `vat_validated_at` nullo (validazione manuale lato admin).
        $viesValid = app(ViesClient::class)->isValid($validated['vat_number']);

        if ($viesValid === false) {
            $this->addError('vat_number', 'La Partita IVA risulta inesistente o non attiva (VIES).');

            return;
        }

        $user = DB::transaction(function () use ($validated, $viesValid) {
            $tenant = Tenant::create([
                'name' => $validated['business_name'],
                'vat_number' => $validated['vat_number'],
                'tax_code' => $validated['tax_code'] ?: null,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'province' => $validated['province'],
                'country' => 'IT',
                'sdi_code' => $validated['sdi_code'] ?: null,
                'pec' => $validated['pec'] ?: null,
                'vat_validated_at' => $viesValid === true ? now() : null,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole(User::ROLE_OWNER);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Dati attività</h2>

        <!-- Ragione sociale -->
        <div class="mt-3">
            <x-input-label for="business_name" value="Ragione sociale" />
            <x-text-input wire:model="business_name" id="business_name" class="block mt-1 w-full" type="text" name="business_name" required autofocus autocomplete="organization" />
            <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
        </div>

        <!-- Partita IVA -->
        <div class="mt-4">
            <x-input-label for="vat_number" value="Partita IVA" />
            <x-text-input wire:model="vat_number" id="vat_number" class="block mt-1 w-full" type="text" name="vat_number" required inputmode="numeric" placeholder="11 cifre" />
            <x-input-error :messages="$errors->get('vat_number')" class="mt-2" />
        </div>

        <!-- Codice Fiscale -->
        <div class="mt-4">
            <x-input-label for="tax_code" value="Codice Fiscale (se diverso dalla P.IVA)" />
            <x-text-input wire:model="tax_code" id="tax_code" class="block mt-1 w-full" type="text" name="tax_code" autocomplete="off" />
            <x-input-error :messages="$errors->get('tax_code')" class="mt-2" />
        </div>

        <!-- Indirizzo -->
        <div class="mt-4">
            <x-input-label for="address" value="Indirizzo" />
            <x-text-input wire:model="address" id="address" class="block mt-1 w-full" type="text" name="address" required autocomplete="street-address" />
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <!-- Città -->
        <div class="mt-4">
            <x-input-label for="city" value="Città" />
            <x-text-input wire:model="city" id="city" class="block mt-1 w-full" type="text" name="city" required />
            <x-input-error :messages="$errors->get('city')" class="mt-2" />
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="postal_code" value="CAP" />
                <x-text-input wire:model="postal_code" id="postal_code" class="block mt-1 w-full" type="text" name="postal_code" required inputmode="numeric" maxlength="5" />
                <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="province" value="Provincia" />
                <x-text-input wire:model="province" id="province" class="block mt-1 w-full uppercase" type="text" name="province" required maxlength="2" placeholder="RG" />
                <x-input-error :messages="$errors->get('province')" class="mt-2" />
            </div>
        </div>

        <h2 class="mt-8 text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Fatturazione elettronica</h2>
        <p class="mt-1 text-xs text-gray-400">Indica il Codice Destinatario SDI <span class="font-medium">oppure</span> la PEC.</p>

        <div class="mt-3 grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="sdi_code" value="Codice SDI" />
                <x-text-input wire:model="sdi_code" id="sdi_code" class="block mt-1 w-full uppercase" type="text" name="sdi_code" maxlength="7" />
                <x-input-error :messages="$errors->get('sdi_code')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="pec" value="PEC" />
                <x-text-input wire:model="pec" id="pec" class="block mt-1 w-full" type="email" name="pec" />
                <x-input-error :messages="$errors->get('pec')" class="mt-2" />
            </div>
        </div>

        <h2 class="mt-8 text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Account</h2>

        <!-- Referente -->
        <div class="mt-3">
            <x-input-label for="name" value="Nome e cognome (referente)" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>
