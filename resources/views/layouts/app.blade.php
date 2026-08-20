<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        {{--
            Guscio del backoffice: sidebar fissa a sinistra da lg in su, drawer
            richiamabile dall'hamburger sotto quella soglia. `open` sta qui
            perché è condiviso fra topbar, overlay e sidebar.
        --}}
        <div x-data="{ open: false }" @keydown.escape.window="open = false"
             class="min-h-screen bg-gray-100 dark:bg-gray-900">

            {{-- Velo dietro al drawer: chiude il menu al tocco fuori --}}
            <div x-show="open" x-cloak x-transition.opacity @click="open = false"
                 class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"></div>

            <livewire:layout.sidebar />

            <div class="lg:pl-64">
                {{-- Topbar: solo mobile, su desktop la navigazione è tutta nella sidebar --}}
                <div class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-800 lg:hidden">
                    <button type="button" @click="open = true"
                            class="-ml-1 rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700">
                        <x-icon name="menu" class="h-6 w-6" />
                        <span class="sr-only">Apri menu</span>
                    </button>

                    <x-application-logo class="h-7 w-auto" />
                    <span class="font-semibold text-gray-900 dark:text-gray-100">Replisa</span>
                </div>

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white shadow dark:bg-gray-800">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-toast-hub />

        <x-cookie-banner />

        @livewireScriptConfig
    </body>
</html>
