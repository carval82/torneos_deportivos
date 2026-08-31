<x-app-layout>
    <x-slot name="header">Delegados</x-slot>
    <x-slot name="subheader">Creá el delegado, vinculalo a un equipo y al torneo. La contraseña inicial es el documento.</x-slot>

    <div class="grid gap-6 lg:grid-cols-5">
        <section class="card p-6 lg:col-span-2 h-fit">
            <h2 class="font-semibold text-lg">Nuevo delegado</h2>
            <p class="text-sm text-slate-500 mt-1 mb-5">
                El equipo se inscribe al torneo automáticamente si todavía no está.
            </p>

            <form method="POST" action="{{ route('organizer.delegates.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm text-slate-600">Torneo</label>
                    <select name="tournament_id" class="field" required>
                        <option value="">Elegí torneo</option>
                        @foreach ($tournaments as $tournament)
                            <option value="{{ $tournament->id }}" @selected((int) old('tournament_id', $selectedTournamentId) === (int) $tournament->id)>
                                {{ $tournament->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tournament_id') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Equipo existente</label>
                    <select name="team_id" class="field">
                        <option value="">Elegí equipo (o creá uno abajo)</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected((int) old('team_id', $selectedTeamId) === (int) $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @error('team_id') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">O crear equipo nuevo</label>
                    <input name="new_team_name" value="{{ old('new_team_name') }}" class="field" placeholder="Ej. Las Tekas">
                    @error('new_team_name') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Nombre completo del delegado</label>
                    <input name="name" value="{{ old('name') }}" class="field" required placeholder="Juan Pérez">
                    @error('name') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Correo</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="field" required placeholder="delegado@club.com">
                    @error('email') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-slate-600">Tipo doc.</label>
                        <select name="document_type" class="field">
                            @foreach (['Cédula', 'DNI', 'Pasaporte'] as $type)
                                <option value="{{ $type }}" @selected(old('document_type', 'Cédula') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Documento (= clave)</label>
                        <input name="document_number" value="{{ old('document_number') }}" class="field" required placeholder="12345678">
                        @error('document_number') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_disciplinary_committee" value="1" class="rounded border-slate-300" @checked(old('is_disciplinary_committee'))>
                    Pertenece al comité disciplinario
                </label>

                <button class="btn-primary w-full" @disabled($tournaments->isEmpty())>
                    Crear y vincular al equipo
                </button>

                @if ($tournaments->isEmpty())
                    <p class="text-xs text-amber-700">
                        Primero creá un torneo.
                        <a href="{{ route('tournaments.create') }}" class="font-semibold underline">Nuevo torneo</a>
                    </p>
                @endif
            </form>
        </section>

        <section class="card p-6 lg:col-span-3">
            <h2 class="font-semibold text-lg mb-4">Delegados cargados</h2>

            @forelse ($delegates as $delegate)
                <div class="rounded-2xl border border-slate-100 px-4 py-3 mb-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-arena-navy">{{ $delegate->name }}</p>
                            <p class="text-sm text-slate-500">{{ $delegate->email }}</p>
                            <p class="text-sm text-slate-500">
                                {{ $delegate->document_type }} {{ $delegate->document_number }}
                                · clave = documento
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($delegate->teams as $team)
                            <span class="inline-flex items-center rounded-full bg-arena-mist px-3 py-1 text-xs font-medium text-arena-navy">
                                {{ $team->name }}
                                @if ($team->pivot->is_disciplinary_committee)
                                    · Comité
                                @endif
                            </span>
                        @empty
                            <span class="text-xs text-slate-400">Sin equipo vinculado</span>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-500">
                    Todavía no hay delegados. Creá el primero con el formulario.
                </div>
            @endforelse

            @if ($tournaments->isNotEmpty())
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <h3 class="font-medium mb-3">Por torneo</h3>
                    <div class="space-y-3">
                        @foreach ($tournaments as $tournament)
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <a href="{{ route('tournaments.show', ['tournament' => $tournament, 'tab' => 'delegados']) }}" class="font-medium text-arena-navy hover:underline">
                                    {{ $tournament->name }}
                                </a>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $tournament->teams->count() }} equipos ·
                                    {{ $tournament->teams->sum(fn ($t) => $t->delegates->count()) }} delegados
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
