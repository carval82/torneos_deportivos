@props([
    'variant' => 'mark', // mark | full
    'size' => 'md',
    'showTagline' => false,
])

@php
    $markClass = match ($size) {
        'sm' => 'h-10 w-10',
        'lg' => 'h-20 w-20',
        'xl' => 'h-28 w-28',
        default => 'h-14 w-14',
    };

    $fullClass = match ($size) {
        'sm' => 'h-12',
        'lg' => 'h-28',
        'xl' => 'h-36',
        default => 'h-20',
    };

    $src = $variant === 'full'
        ? asset('images/brand/logo-320.png')
        : asset('images/brand/mark-192.png');
@endphp

<a {{ $attributes->class(['group inline-flex flex-col items-center text-center'])->merge(['href' => route('dashboard')]) }}>
    @if ($variant === 'full')
        <img
            src="{{ $src }}"
            alt="Arena Players"
            class="{{ $fullClass }} w-auto object-contain drop-shadow-sm group-hover:scale-[1.02] transition"
        >
    @else
        <span class="{{ $markClass }} shrink-0 rounded-2xl overflow-hidden bg-white shadow-sm ring-1 ring-arena-navy/10 group-hover:shadow-md transition">
            <img
                src="{{ $src }}"
                alt="Arena Players"
                class="h-full w-full object-contain p-1"
                width="192"
                height="192"
            >
        </span>
    @endif

    @if ($showTagline)
        <span class="mt-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-arena-lime">
            Torneos · Eventos · Pasión
        </span>
    @endif
</a>
