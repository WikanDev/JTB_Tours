@props([
    'href',
])

<a 
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-2 bg-gradient-to-r from-amber-400 to-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:from-amber-500 hover:to-orange-600 active:from-amber-600 active:to-orange-700 focus:outline-none transition ease-in-out duration-150 shadow-sm']) }}
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
    </svg>
    {{ $slot }}
</a>
