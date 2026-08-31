<x-app-layout>
    <x-slot name="header">{{ $team->name }}</x-slot>
    <x-slot name="subheader">{{ $team->city }} · DT {{ $team->coach ?: 'sin asignar' }}</x-slot>

    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('players.create', ['team_id' => $team->id]) }}" class="btn-primary">Agregar jugador</a>
        <a href="{{ route('organizer.delegates.index', ['team_id' => $team->id]) }}" class="btn-ghost">Asignar delegado</a>
        <a href="{{ route('teams.edit', $team) }}" class="btn-ghost">Editar equipo</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-5 mb-8">
        <section class="card p-6 lg:col-span-2 h-fit">
            <h2 class="font-semibold">Delegado de este equipo</h2>
            <p class="text-sm text-slate-500 mt-1 mb-4">Se vincula al equipo y se inscribe al torneo si hace falta.</p>

            @if ($team->delegates->isNotEmpty())
                <div class="mb-4 space-y-2">
                    @foreach ($team->delegates as $delegate)
                        <div class="rounded-xl bg-arena-mist px-3 py-2 text-sm">
                            <p class="font-medium text-arena-navy">{{ $delegate->name }}</p>
                            <p class="text-slate-500">{{ $delegate->email }} · {{ $delegate->document_type }} {{ $delegate->document_number }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($tournaments->isEmpty())
                <p class="text-sm text-amber-700">
                    Primero creá un torneo para vincular al delegado.
                    <a href="{{ route('tournaments.create') }}" class="font-semibold underline">Nuevo torneo</a>
                </p>
            @else
                <form method="POST" action="{{ route('organizer.delegates.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                    <div>
                        <label class="text-sm text-slate-600">Torneo</label>
                        <select name="tournament_id" class="field" required>
                            @foreach ($tournaments as $tournament)
                                <option value="{{ $tournament->id }}">{{ $tournament->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input name="name" class="field" required placeholder="Nombre completo">
                    <input type="email" name="email" class="field" required placeholder="Correo">
                    <div class="grid grid-cols-2 gap-2">
                        <select name="document_type" class="field">
                            <option value="Cédula">Cédula</option>
                            <option value="DNI">DNI</option>
                            <option value="Pasaporte">Pasaporte</option>
                        </select>
                        <input name="document_number" class="field" required placeholder="Documento (= clave)">
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="is_disciplinary_committee" value="1" class="rounded border-slate-300">
                        Comité disciplinario
                    </label>
                    <button class="btn-primary w-full">Crear y vincular</button>
                </form>
            @endif
        </section>

        <section class="lg:col-span-3">
            <h2 class="font-semibold mb-4">Plantilla</h2>
            <div class="grid gap-4 md:grid-cols-2">
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
            <div class="card p-8 text-slate-500 md:col-span-2">Este equipo todavía no tiene planilla.</div>
        @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
