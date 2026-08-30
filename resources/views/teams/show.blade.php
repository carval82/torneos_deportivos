<x-app-layout>
    <x-slot name="header">{{ $team->name }}</x-slot>
    <x-slot name="subheader">{{ $team->city }} · DT {{ $team->coach ?: 'sin asignar' }}</x-slot>

    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('players.create', ['team_id' => $team->id]) }}" class="btn-primary">Agregar jugador</a>
        <a href="{{ route('teams.edit', $team) }}" class="btn-ghost">Editar equipo</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($team->players as $player)
            <a href="{{ route('players.show', $player) }}" class="card p-5 hover:border-arena-lime">
                <div class="flex gap-4">
                    <div class="h-16 w-16 rounded-2xl overflow-hidden bg-slate-50 shrink-0">
                        @if ($player->photoUrl())
                            <img src="{{ $player->photoUrl() }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div>
                        <p class="font-semibold">{{ $player->jersey_number ? '#'.$player->jersey_number.' ' : '' }}{{ $player->displayName() }}</p>
                        <p class="text-sm text-slate-500">{{ $player->position ?: 'Sin posición' }} · {{ $player->age() ?? '—' }} años</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $player->document_type }} {{ $player->document_number }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="card p-8 text-slate-500 md:col-span-3">Este equipo todavía no tiene planilla.</div>
        @endforelse
    </div>
</x-app-layout>
