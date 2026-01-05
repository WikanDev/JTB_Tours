@props([
    'type' => 'submit',
    'href' => null,
])

@if($href)
    <a 
        href="{{ $href }}" 
        {{ $attributes->merge(['class' => 'h-[42px] inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}
    >
        {{ $slot }}
    </a>
@else
    <button 
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'h-[42px] inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}
    >
        {{ $slot }}
    </button>
@endif
