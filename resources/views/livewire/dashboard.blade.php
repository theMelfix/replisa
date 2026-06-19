<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-1">
            Ciao {{ auth()->user()->name }} 👋
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">{{ auth()->user()->tenant?->name }}</p>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                ['Messaggi inviati', $inviati, 'text-green-600'],
                ['Messaggi ricevuti', $ricevuti, 'text-blue-600'],
                ['Contatti', $contatti, 'text-gray-800 dark:text-gray-100'],
                ['Conversazioni attive', $conversazioniAttive, 'text-amber-600'],
                ['Appuntamenti in arrivo', $appuntamentiProssimi, 'text-purple-600'],
            ] as [$label, $value, $color])
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex gap-3">
            <a href="{{ route('automations') }}" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-white text-sm font-semibold hover:bg-green-700">Gestisci automazioni</a>
        </div>
    </div>
</div>
