<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Livewire\Volt\Component;

/**
 * Sidebar del backoffice.
 *
 * Sono due navigazioni distinte che condividono lo stesso guscio: il
 * super-admin (che non appartiene ad alcun tenant, {@see User::isSuperAdmin()})
 * vede solo le voci di piattaforma, il cliente solo le proprie. Prima le due
 * navigazioni convivevano nella stessa barra e l'admin si ritrovava link a
 * pagine tenant che per lui non hanno senso.
 */
new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Voci di menu del cliente, raggruppate per area.
     *
     * @return array<int, array{label: ?string, items: array<int, array{label: string, route: string, icon: string, match?: string}>}>
     */
    protected function tenantSections(): array
    {
        return [
            ['label' => null, 'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
            ]],
            ['label' => 'Operatività', 'items' => [
                ['label' => 'Contatti', 'route' => 'contacts', 'icon' => 'users'],
                ['label' => 'Messaggi', 'route' => 'messages', 'icon' => 'chat'],
                ['label' => 'Appuntamenti', 'route' => 'appointments', 'icon' => 'calendar'],
            ]],
            ['label' => 'Automazioni', 'items' => [
                ['label' => 'Flussi', 'route' => 'automations', 'icon' => 'bolt'],
                ['label' => 'Campagne', 'route' => 'campaigns', 'icon' => 'megaphone'],
                ['label' => 'Scadenze', 'route' => 'deadlines', 'icon' => 'clock'],
            ]],
            ['label' => 'Impostazioni', 'items' => [
                ['label' => 'Account WhatsApp', 'route' => 'whatsapp', 'icon' => 'phone'],
                ['label' => 'API & Integrazioni', 'route' => 'api-tokens', 'icon' => 'code'],
                ['label' => 'Abbonamento', 'route' => 'billing', 'icon' => 'card'],
            ]],
        ];
    }

    /**
     * Voci di menu del super-admin: solo le rotte di piattaforma esistenti.
     *
     * @return array<int, array{label: ?string, items: array<int, array{label: string, route: string, icon: string, match?: string}>}>
     */
    protected function adminSections(): array
    {
        return [
            ['label' => null, 'items' => [
                ['label' => 'Panoramica', 'route' => 'admin.overview', 'icon' => 'home'],
                // `match`: la voce resta accesa anche sulla scheda del singolo cliente.
                ['label' => 'Clienti', 'route' => 'admin.tenants', 'icon' => 'building', 'match' => 'admin.tenants*'],
                ['label' => 'Richieste demo', 'route' => 'admin.leads', 'icon' => 'inbox'],
                ['label' => 'Scadenze nazionali', 'route' => 'admin.deadlines', 'icon' => 'calendar-days'],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $user = auth()->user();
        $isAdmin = $user->isSuperAdmin();

        return [
            'isAdmin' => $isAdmin,
            'sections' => $isAdmin ? $this->adminSections() : $this->tenantSections(),
            // Sotto il logo: chi sei e dove sei. Per il cliente è il nome
            // dell'attività, per l'admin l'area in cui si trova.
            'contextLabel' => $isAdmin ? 'Amministrazione' : ($user->tenant?->name ?? '—'),
            'homeRoute' => $isAdmin ? route('admin.overview') : route('dashboard'),
        ];
    }
}; ?>

{{--
    Fuori schermo su mobile finché non si apre il drawer (`open` vive nel layout,
    condiviso con l'hamburger della topbar); su desktop `lg:translate-x-0` vince
    sulla media query e la sidebar è sempre fissa a sinistra.
--}}
<div :class="{ 'translate-x-0': open, '-translate-x-full': ! open }"
     class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 dark:border-gray-700 dark:bg-gray-800 lg:translate-x-0">

    {{-- Intestazione: logo + contesto --}}
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-gray-200 px-4 dark:border-gray-700">
        <a href="{{ $homeRoute }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
            <x-application-logo class="h-8 w-auto shrink-0" />
            <span class="min-w-0">
                <span class="block text-sm font-semibold leading-tight text-gray-900 dark:text-gray-100">Replisa</span>
                <span class="block truncate text-xs leading-tight {{ $isAdmin ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $contextLabel }}
                </span>
            </span>
        </a>

        {{-- Chiusura del drawer: solo mobile, su desktop la sidebar è fissa --}}
        <button type="button" @click="open = false"
                class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 lg:hidden">
            <x-icon name="close" class="h-5 w-5" />
            <span class="sr-only">Chiudi menu</span>
        </button>
    </div>

    {{-- Voci di navigazione --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        @foreach ($sections as $section)
            <div class="space-y-1">
                @if ($section['label'])
                    <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        {{ $section['label'] }}
                    </p>
                @endif

                @foreach ($section['items'] as $item)
                    <x-backoffice.nav-item :href="route($item['route'])"
                                           :icon="$item['icon']"
                                           :active="request()->routeIs($item['match'] ?? $item['route'])"
                                           @click="open = false">
                        {{ $item['label'] }}
                    </x-backoffice.nav-item>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- Utente: profilo e uscita --}}
    <div x-data="{ menu: false }" @click.outside="menu = false"
         class="relative shrink-0 border-t border-gray-200 p-3 dark:border-gray-700">

        <div x-show="menu" x-cloak x-transition
             class="absolute bottom-full left-3 right-3 mb-1 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <a href="{{ route('profile') }}" wire:navigate @click="menu = false; open = false"
               class="flex items-center gap-3 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                <x-icon name="user" class="h-4 w-4 text-gray-400" /> Profilo
            </a>
            <button type="button" wire:click="logout"
                    class="flex w-full items-center gap-3 px-3 py-2 text-start text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                <x-icon name="logout" class="h-4 w-4 text-gray-400" /> Esci
            </button>
        </div>

        <button type="button" @click="menu = ! menu"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-start hover:bg-gray-100 dark:hover:bg-gray-700">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-semibold text-green-700 dark:bg-green-500/15 dark:text-green-400">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                      x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                      x-on:profile-updated.window="name = $event.detail.name"></span>
                <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</span>
            </span>
            <x-icon name="selector" class="h-4 w-4 shrink-0 text-gray-400" />
        </button>
    </div>
</div>
