@props(['href', 'icon' => 'home', 'active' => false])

{{--
    Voce della sidebar del backoffice. Lo stato attivo è passato dal chiamante
    (che lo calcola con request()->routeIs) così la voce resta agnostica rispetto
    al nome della rotta.
--}}

<a href="{{ $href }}" wire:navigate
   @if ($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition '.
        ($active
            ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400'
            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100')]) }}>
    <x-icon :name="$icon"
            class="w-5 h-5 shrink-0 {{ $active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}" />
    <span class="truncate">{{ $slot }}</span>
</a>
