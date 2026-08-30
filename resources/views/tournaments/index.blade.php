<x-app-layout>
    <x-slot name="header">Torneos</x-slot>
    <x-slot name="subheader">Vos definís edad, reglamento, días de juego y canchas del complejo.</x-slot>

    <div class="flex justify-end mb-6">
        <a href="{{ route('tournaments.create') }}" class="btn-primary">Crear torneo</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($tournaments as $tournament)
            <a href="{{ route('tournaments.show', $tournament) }}" class="card p-6 hover:border-arena-lime transition">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-arena-navy">{{ $tournament->sport->name }}</p>
                        <h2 class="text-xl font-semibold mt-1">{{ $tournament->name }}</h2>
                        <p class="text-sm text-slate-500 mt-1">{{ $tournament->ageLabel() }} · {{ $tournament->playDaysLabel() }} · {{ $tournament->season }}</p>
                    </div>
                    <span class="soft-chip">{{ $tournament->statusLabel() }}</span>
                </div>
                <div class="mt-6 flex flex-wrap gap-4 text-sm text-slate-600">
                    <span>{{ $tournament->teams_count }}{{ $tournament->max_teams ? '/'.$tournament->max_teams : '' }} equipos</span>
                    <span>{{ $tournament->games_count }} partidos</span>
                    <span>{{ count($tournament->fieldList()) }} canchas</span>
                </div>
            </a>
        @empty
            <div class="card p-8 md:col-span-2 text-slate-500">
                No hay torneos todavía. Creá uno, inscribí equipos y generá el fixture.
            </div>
        @endforelse
    </div>
</x-app-layout>
