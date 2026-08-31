<x-app-layout>
    <x-slot name="header">Nuevo torneo</x-slot>
    <x-slot name="subheader">Definí vos la edad, el reglamento, los días y las canchas del complejo.</x-slot>
    @isset($billingNote)
        <div class="mb-4 rounded-2xl border border-arena-lime/40 bg-arena-lime/15 px-4 py-3 text-sm text-arena-navy">
            {{ $billingNote }}
        </div>
    @endisset
    <div class="card p-6 max-w-5xl">
        <form method="POST" action="{{ route('tournaments.store') }}">
            @include('tournaments._form', ['submit' => 'Crear torneo'])
        </form>
    </div>
</x-app-layout>
