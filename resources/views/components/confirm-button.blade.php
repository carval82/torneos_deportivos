@props([
    'action',
    'method' => 'post',
    'title' => '¿Confirmar?',
    'message' => '',
    'confirm' => 'Confirmar',
    'cancel' => 'Cancelar',
    'tone' => 'amber',
])

@php
    $httpMethod = strtoupper($method);
    $submitClass = match ($tone) {
        'danger' => 'btn-danger',
        'primary' => 'btn-primary',
        default => 'btn-primary !bg-amber-600 hover:!bg-amber-700',
    };
@endphp

<div x-data="{ open: false }" class="relative inline-flex w-full">
    <button type="button" {{ $attributes->merge(['class' => 'w-full']) }} @click="open = true">
        {{ $slot }}
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="if (open) open = false"
    >
        <div class="absolute inset-0 bg-slate-900/40" @click="open = false"></div>

        <div
            x-show="open"
            x-transition
            class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl"
            @click.stop
        >
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
            @if ($message !== '')
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $message }}</p>
            @endif

            <form method="POST" action="{{ $action }}" class="mt-5 space-y-4">
                @csrf
                @if ($httpMethod !== 'POST')
                    @method($httpMethod)
                @endif
                {{ $form ?? '' }}
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" class="btn-ghost" @click="open = false">{{ $cancel }}</button>
                    <button type="submit" class="{{ $submitClass }}">{{ $confirm }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
