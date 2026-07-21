<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Replisa — Automazione WhatsApp per la tua attività</title>
    <meta name="description" content="Replisa automatizza WhatsApp per PMI: messaggi di benvenuto, promemoria appuntamenti e richieste di recensione. Meno lavoro manuale, più clienti che tornano.">

    <meta property="og:title" content="Replisa — Automazione WhatsApp per PMI">
    <meta property="og:description" content="Benvenuti automatici, promemoria appuntamenti e richieste di recensione su WhatsApp.">
    <meta property="og:type" content="website">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans text-gray-800 bg-white">

    {{-- Navbar --}}
    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-100">
        <nav class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="text-xl font-bold tracking-tight text-gray-900">Repl<span class="text-green-600">i</span>sa</a>
            <div class="hidden sm:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="#funzioni" class="hover:text-gray-900">Funzioni</a>
                <a href="#prezzi" class="hover:text-gray-900">Prezzi</a>
                <a href="#demo" class="hover:text-gray-900">Demo</a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="hover:text-gray-900">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-gray-900">Accedi</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-white hover:bg-green-700">Inizia ora</a>
                    @endauth
                @endif
            </div>
        </nav>
    </header>

    {{-- Hero --}}
    <section class="bg-gradient-to-b from-green-50 to-white">
        <div class="max-w-6xl mx-auto px-6 py-24 text-center">
            <span class="inline-block rounded-full bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 mb-6">WhatsApp Business Platform ufficiale</span>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900 max-w-3xl mx-auto leading-tight">
                Automatizza WhatsApp e fai tornare i tuoi clienti
            </h1>
            <p class="mt-6 text-lg text-gray-600 max-w-2xl mx-auto">
                Replisa risponde ai clienti, ricorda appuntamenti e scadenze, e invia comunicazioni al posto tuo — sul canale che i tuoi clienti usano davvero. Meno lavoro manuale, più tempo per la tua attività.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ Route::has('register') ? route('register') : '#prezzi' }}" class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-white font-semibold hover:bg-green-700">Inizia ora</a>
                <a href="#funzioni" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-700 hover:bg-gray-50">Scopri come funziona</a>
            </div>
        </div>
    </section>

    {{-- Funzioni / automazioni --}}
    <section id="funzioni" class="max-w-6xl mx-auto px-6 py-20">
        <h2 class="text-3xl font-bold text-center text-gray-900">Tre automazioni che lavorano per te</h2>
        <p class="mt-3 text-center text-gray-600 max-w-2xl mx-auto">Pronte all'uso, personalizzabili per la tua attività.</p>

        <div class="mt-14 grid gap-8 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-100 p-8 shadow-sm">
                <div class="h-12 w-12 rounded-xl bg-green-100 text-green-700 flex items-center justify-center text-2xl">👋</div>
                <h3 class="mt-5 text-lg font-semibold text-gray-900">Benvenuto automatico</h3>
                <p class="mt-2 text-gray-600">Chi ti scrive per la prima volta riceve subito un menu interattivo con le tue opzioni: informazioni, prenotazioni, contatto diretto.</p>
            </div>
            <div class="rounded-2xl border border-gray-100 p-8 shadow-sm">
                <div class="h-12 w-12 rounded-xl bg-green-100 text-green-700 flex items-center justify-center text-2xl">⏰</div>
                <h3 class="mt-5 text-lg font-semibold text-gray-900">Promemoria &amp; Scadenze</h3>
                <p class="mt-2 text-gray-600">Ricorda appuntamenti e scadenze importanti (IMU, 730, rinnovi) al momento giusto, con o senza conferma. Meno dimenticanze, più puntualità.</p>
            </div>
            <div class="rounded-2xl border border-gray-100 p-8 shadow-sm">
                <div class="h-12 w-12 rounded-xl bg-green-100 text-green-700 flex items-center justify-center text-2xl">📣</div>
                <h3 class="mt-5 text-lg font-semibold text-gray-900">Campagne e comunicazioni</h3>
                <p class="mt-2 text-gray-600">Invia comunicazioni e promozioni a liste o segmenti di contatti: avvisi, offerte e scadenze collettive, in pochi clic.</p>
            </div>
        </div>

        <p class="mt-10 text-center text-sm text-gray-500">
            Vuoi più recensioni? <span class="font-semibold text-gray-700">Richiesta recensione Google</span> è disponibile come add-on.
        </p>
    </section>

    {{-- Prezzi --}}
    <section id="prezzi" class="bg-gray-50 border-y border-gray-100" x-data="{ annual: false }">
        <div class="max-w-6xl mx-auto px-6 py-20">
            <h2 class="text-3xl font-bold text-center text-gray-900">Prezzi semplici, senza sorprese</h2>
            <p class="mt-3 text-center text-gray-600">Prova gratis 14 giorni. I costi di conversazione WhatsApp di Meta sono a parte.</p>

            <div class="mt-8 flex justify-center">
                <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
                    <button type="button" @click="annual = false" :class="!annual ? 'bg-green-600 text-white' : 'text-gray-600'" class="px-4 py-1.5 rounded-md font-semibold">Mensile</button>
                    <button type="button" @click="annual = true" :class="annual ? 'bg-green-600 text-white' : 'text-gray-600'" class="px-4 py-1.5 rounded-md font-semibold">Annuale <span class="text-xs">-20%</span></button>
                </div>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Starter', 19, 182, 'Per iniziare', ['1 automazione', 'Fino a 500 contatti', 'Supporto email']],
                    ['Base', 49, 470, 'Il più scelto', ['Tutte le automazioni', '1.000 messaggi campagna/mese', 'Fino a 2.000 contatti']],
                    ['Pro', 99, 950, 'Per chi cresce', ['Contatti illimitati', '5.000 messaggi campagna/mese', 'API + Multi-operatore']],
                    ['Business', 199, 1910, 'Su misura', ['Tutto del piano Pro', 'Recensioni Google incluse', 'Onboarding + SLA dedicati']],
                ] as [$nome, $prezzo, $prezzoAnnuo, $tag, $features])
                    <div class="rounded-2xl bg-white border @if($nome==='Base') border-green-500 ring-2 ring-green-500 @else border-gray-200 @endif p-6 flex flex-col">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $nome }}</h3>
                            @if($nome==='Base')<span class="text-xs font-semibold text-green-700 bg-green-100 rounded-full px-2 py-0.5">Popolare</span>@endif
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $tag }}</p>
                        <p class="mt-4">
                            <span x-show="!annual"><span class="text-4xl font-bold text-gray-900">€{{ $prezzo }}</span><span class="text-gray-500">/mese</span></span>
                            <span x-show="annual" x-cloak><span class="text-4xl font-bold text-gray-900">€{{ $prezzoAnnuo }}</span><span class="text-gray-500">/anno</span></span>
                        </p>
                        <ul class="mt-6 space-y-2 text-sm text-gray-600 flex-1">
                            @foreach ($features as $f)
                                <li class="flex items-start gap-2"><span class="text-green-600">✓</span>{{ $f }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-6 inline-flex justify-center rounded-lg @if($nome==='Base') bg-green-600 text-white hover:bg-green-700 @else border border-gray-300 text-gray-700 hover:bg-gray-50 @endif px-4 py-2 font-semibold">Scegli {{ $nome }}</a>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 text-center text-sm text-gray-500">
                Add-on <span class="font-semibold text-gray-700">Recensioni Google</span> +€19/mese — incluso nel piano Business.
            </p>
        </div>
    </section>

    {{-- CTA finale + form contatto/demo (E5.1.2) --}}
    <section id="demo" class="bg-gray-50 border-t border-gray-100">
        <div class="max-w-3xl mx-auto px-6 py-20">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Pronto a far lavorare WhatsApp per te?</h2>
                <p class="mt-3 text-gray-600">Attiva Replisa oggi o prenota una demo di 15 minuti. Lasciaci i tuoi dati, ti ricontattiamo noi.</p>
                <div class="mt-6">
                    <a href="{{ Route::has('register') ? route('register') : '#' }}" class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-white font-semibold hover:bg-green-700">Inizia ora</a>
                </div>
            </div>
            <div class="mt-10">
                <livewire:contact-form />
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500">
            <p>© {{ date('Y') }} Replisa — Giovanni Melfi · P.IVA IT01809180886</p>
            <div class="flex gap-6">
                <a href="/privacy" class="hover:text-gray-800">Privacy</a>
                <a href="/termini" class="hover:text-gray-800">Condizioni d'uso</a>
                <a href="/eliminazione-dati" class="hover:text-gray-800">Eliminazione dati</a>
            </div>
        </div>
    </footer>

    <x-cookie-banner />

</body>
</html>
