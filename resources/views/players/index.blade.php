<x-app-layout>
    <x-slot name="header">Jugadores</x-slot>
    <x-slot name="subheader">Fichas, documentos y control de edad.</x-slot>

    <div class="flex flex-col sm:flex-row gap-3 justify-between mb-6">
        <form class="flex gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o documento" class="field w-72">
            <button class="btn-ghost">Buscar</button>
        </form>
        <a href="{{ route('players.create') }}" class="btn-primary">Nueva ficha</a>
    </div>

    <div class="card overflow-hidden">
        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr><th></th><th>Jugador</th><th>Equipo</th><th>Documento</th><th>Edad</th><th>Dorsal</th></tr>
                </thead>
                <tbody>
                    @forelse ($players as $player)
                        <tr>
                            <td>
                                <div class="h-10 w-10 rounded-xl overflow-hidden bg-slate-50">
                                    @if ($player->photoUrl())
                                        <img src="{{ $player->photoUrl() }}" class="h-full w-full object-cover" alt="">
                                    @endif
                                </div>
                            </td>
                            <td><a href="{{ route('players.show', $player) }}" class="text-arena-navy font-medium">{{ $player->fullName() }}</a></td>
                            <td>{{ $player->team?->name ?? 'Libre' }}</td>
                            <td>{{ $player->document_type }} {{ $player->document_number }}</td>
                            <td>{{ $player->age() ?? '—' }}</td>
                            <td>{{ $player->jersey_number ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-slate-500">No hay jugadores cargados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $players->links() }}</div>
</x-app-layout>
