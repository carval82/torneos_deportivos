<x-app-layout>
    <x-slot name="header">Equipos</x-slot>
    <x-slot name="subheader">Clubes, planteles y colores.</x-slot>

    <div class="flex justify-end mb-6">
        <a href="{{ route('teams.create') }}" class="btn-primary">Nuevo equipo</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($teams as $team)
            <a href="{{ route('teams.show', $team) }}" class="card p-6 hover:border-arena-lime transition">
                <div class="flex items-center gap-4">
                    <span class="h-14 w-14 rounded-2xl grid place-items-center font-bold" style="background: {{ $team->primary_color ?: '#10b981' }}; color:#021014">
                        {{ $team->initials() }}
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold">{{ $team->name }}</h2>
                        <p class="text-sm text-slate-500">{{ $team->city ?: 'Sin ciudad' }} · {{ $team->players_count }} jugadores</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="card p-8 text-slate-500">Todavía no hay equipos.</div>
        @endforelse
    </div>
</x-app-layout>
