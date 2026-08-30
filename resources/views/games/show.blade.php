<x-app-layout>
    <x-slot name="header">{{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }}</x-slot>
    <x-slot name="subheader">
        {{ $game->tournament->name }} · {{ $game->round_name }} ·
        {{ optional($game->scheduled_at)->format('d/m H:i') }} ·
        {{ $game->locationLabel() }} · {{ $game->statusLabel() }}
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('tournaments.show', ['tournament' => $game->tournament, 'tab' => 'fixture']) }}" class="text-sm text-arena-navy font-medium">← Volver al fixture</a>
    </div>

    {{-- Marcador --}}
    <section class="card p-6 mb-6">
        <div class="flex items-center justify-between text-center mb-6">
            <div class="flex-1">
                <p class="text-sm text-slate-500">Local</p>
                <p class="text-xl font-semibold">{{ $game->homeTeam->name }}</p>
            </div>
            <div class="px-6">
                <p class="text-5xl font-black text-arena-navy">{{ $game->scoreline() }}</p>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-500">Visitante</p>
                <p class="text-xl font-semibold">{{ $game->awayTeam->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('games.score', $game) }}" class="grid sm:grid-cols-4 gap-3">
            @csrf
            @method('PATCH')
            <div>
                <label class="text-xs text-slate-500">Goles local</label>
                <input type="number" name="home_score" value="{{ $game->home_score ?? 0 }}" class="field" min="0">
            </div>
            <div>
                <label class="text-xs text-slate-500">Goles visita</label>
                <input type="number" name="away_score" value="{{ $game->away_score ?? 0 }}" class="field" min="0">
            </div>
            <div>
                <label class="text-xs text-slate-500">Estado</label>
                <select name="status" class="field">
                    @foreach (['scheduled' => 'Programado', 'live' => 'En juego', 'finished' => 'Finalizado', 'postponed' => 'Aplazado'] as $value => $label)
                        <option value="{{ $value }}" @selected($game->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button class="btn-primary w-full">Guardar resultado</button>
            </div>
        </form>
    </section>

    <div class="grid gap-6 xl:grid-cols-2 mb-6">
        {{-- Goles --}}
        <section class="card p-6">
            <h2 class="font-semibold mb-1">1. Goles</h2>
            <p class="text-sm text-slate-500 mb-4">Si cargás goles, el marcador se actualiza solo.</p>
            <form method="POST" action="{{ route('games.events.store', $game) }}" class="grid sm:grid-cols-2 gap-3">
                @csrf
                <input type="hidden" name="type" value="goal">
                <select name="team_id" class="field" required>
                    <option value="{{ $game->home_team_id }}">{{ $game->homeTeam->name }}</option>
                    <option value="{{ $game->away_team_id }}">{{ $game->awayTeam->name }}</option>
                </select>
                <select name="player_id" class="field" required>
                    <option value="">Jugador</option>
                    @foreach ($rosters as $teamRoster)
                        @foreach ($teamRoster as $row)
                            <option value="{{ $row->player_id }}">{{ $row->player->displayName() }}</option>
                        @endforeach
                    @endforeach
                </select>
                <input type="number" name="minute" class="field" placeholder="Minuto" min="1" max="130">
                <button class="btn-primary">Sumar gol</button>
            </form>
            <div class="mt-4 space-y-2">
                @forelse ($goals as $event)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm">
                        <span>{{ $event->minute ? $event->minute."'" : '—' }} · {{ $event->label() }} · {{ $event->player?->displayName() ?? 's/n' }}</span>
                        <form method="POST" action="{{ route('games.events.destroy', [$game, $event]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-rose-600 text-xs">Quitar</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sin goles cargados.</p>
                @endforelse
            </div>
        </section>

        {{-- Tarjetas --}}
        <section class="card p-6">
            <h2 class="font-semibold mb-1">2. Tarjetas</h2>
            <p class="text-sm text-slate-500 mb-4">
                2 amarillas en el mismo partido = expulsión +
                {{ $game->tournament->double_yellow_ban_matches ?: 1 }} fecha(s).
                Roja = {{ $game->tournament->red_ban_matches ?: 1 }} fecha(s).
            </p>
            <form method="POST" action="{{ route('games.events.store', $game) }}" class="grid sm:grid-cols-2 gap-3">
                @csrf
                <select name="team_id" class="field" required>
                    <option value="{{ $game->home_team_id }}">{{ $game->homeTeam->name }}</option>
                    <option value="{{ $game->away_team_id }}">{{ $game->awayTeam->name }}</option>
                </select>
                <select name="player_id" class="field" required>
                    <option value="">Jugador</option>
                    @foreach ($rosters as $teamRoster)
                        @foreach ($teamRoster as $row)
                            @php $ban = $suspensions[$row->player_id] ?? null; @endphp
                            <option value="{{ $row->player_id }}">
                                {{ $row->player->displayName() }}{{ $ban ? ' (sancionado '.$ban->matches_remaining.')' : '' }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
                <select name="type" class="field" required>
                    <option value="yellow">🟨 Amarilla</option>
                    <option value="red">🟥 Roja directa</option>
                </select>
                <input type="number" name="minute" class="field" placeholder="Minuto" min="1" max="130">
                <input name="note" class="field sm:col-span-2" placeholder="Motivo (opcional)">
                <button class="btn-primary sm:col-span-2">Cargar tarjeta</button>
            </form>
            <div class="mt-4 space-y-2">
                @forelse ($cards as $event)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm">
                        <span>
                            {{ $event->type === 'yellow' ? '🟨' : '🟥' }}
                            {{ $event->minute ? $event->minute."'" : '—' }} ·
                            {{ $event->player?->displayName() ?? 's/n' }}
                            @if ($event->note)
                                · <span class="text-slate-500">{{ $event->note }}</span>
                            @endif
                        </span>
                        <form method="POST" action="{{ route('games.events.destroy', [$game, $event]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-rose-600 text-xs">Quitar</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sin tarjetas.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if ($game->status !== 'finished')
        <section class="card p-6 mb-6 border-amber-200">
            <h2 class="font-semibold mb-1">3. Walkover (no se presentó)</h2>
            <p class="text-sm text-slate-500 mb-4">
                Resultado {{ $competitionRules['walkover_goals_for'] }}-{{ $competitionRules['walkover_goals_against'] }}.
                Tras {{ $competitionRules['max_no_shows_before_dq'] }} inasistencia(s) el equipo queda descalificado
                y sus partidos pendientes se dan por W.O. al rival.
            </p>
            <div class="grid sm:grid-cols-2 gap-3">
                <x-confirm-button
                    :action="route('games.walkover', $game)"
                    title="Confirmar walkover"
                    :message="'No se presentó '.$game->homeTeam->name.' (local). El rival gana '.$competitionRules['walkover_goals_for'].'-'.$competitionRules['walkover_goals_against'].' y esto afecta la tabla. Puede descalificar si llega al tope de inasistencias.'"
                    confirm="Confirmar W.O."
                    tone="amber"
                    class="btn-ghost w-full text-amber-800 border-amber-200"
                >
                    <x-slot:form>
                        <input type="hidden" name="absent_team_id" value="{{ $game->home_team_id }}">
                        <input type="hidden" name="note" value="No se presentó el local">
                    </x-slot:form>
                    No se presentó {{ $game->homeTeam->name }} (local)
                </x-confirm-button>

                <x-confirm-button
                    :action="route('games.walkover', $game)"
                    title="Confirmar walkover"
                    :message="'No se presentó '.$game->awayTeam->name.' (visita). El rival gana '.$competitionRules['walkover_goals_for'].'-'.$competitionRules['walkover_goals_against'].' y esto afecta la tabla. Puede descalificar si llega al tope de inasistencias.'"
                    confirm="Confirmar W.O."
                    tone="amber"
                    class="btn-ghost w-full text-amber-800 border-amber-200"
                >
                    <x-slot:form>
                        <input type="hidden" name="absent_team_id" value="{{ $game->away_team_id }}">
                        <input type="hidden" name="note" value="No se presentó la visita">
                    </x-slot:form>
                    No se presentó {{ $game->awayTeam->name }} (visita)
                </x-confirm-button>
            </div>
        </section>
    @elseif ($game->isWalkover())
        <section class="card p-6 mb-6 border-amber-200 bg-amber-50">
            <h2 class="font-semibold text-amber-900">Partido resuelto por W.O.</h2>
            <p class="text-sm text-amber-800 mt-1">
                {{ $game->walkoverReasonLabel() }}:
                {{ $game->walkoverAgainstTeam?->name ?? 'equipo' }}.
                Marcador {{ $game->scoreline() }}.
            </p>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-3 mb-6">
        {{-- Reprogramar partido individual --}}
        <section class="card p-6 xl:col-span-1">
            <h2 class="font-semibold mb-1">4. Mover este partido</h2>
            <p class="text-sm text-slate-500 mb-4">Para aplazar toda la fecha usá el botón del fixture.</p>
            <form method="POST" action="{{ route('games.reschedule', $game) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <div>
                    <label class="text-sm text-slate-600">Nueva fecha y hora</label>
                    <input type="datetime-local" name="scheduled_at" class="field"
                           value="{{ optional($game->scheduled_at)->format('Y-m-d\TH:i') }}">
                </div>
                <div>
                    <label class="text-sm text-slate-600">Cancha</label>
                    <select name="field_name" class="field">
                        @foreach ($game->tournament->fieldList() as $field)
                            <option value="{{ $field }}" @selected($game->field_name === $field)>{{ $field }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Estado</label>
                    <select name="status" class="field">
                        <option value="scheduled" @selected($game->status === 'scheduled')>Programado</option>
                        <option value="postponed" @selected($game->status === 'postponed')>Aplazado</option>
                        <option value="live" @selected($game->status === 'live')>En juego</option>
                        <option value="finished" @selected($game->status === 'finished')>Finalizado</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Motivo</label>
                    <input name="postpone_reason" value="{{ old('postpone_reason', $game->postpone_reason) }}" class="field" placeholder="Lluvia, cancha en mal estado...">
                </div>
                <input type="hidden" name="is_tentative" value="1">
                <button class="btn-primary w-full">Guardar cambio</button>
            </form>
        </section>

        <section class="card p-6 xl:col-span-2">
            <h2 class="font-semibold mb-1">5. Asistencia</h2>
            <p class="text-sm text-slate-500 mb-4">Marcá titulares, suplentes o ausentes.</p>
            <form method="POST" action="{{ route('games.attendance', $game) }}">
                @csrf
                <div class="grid lg:grid-cols-2 gap-6">
                    @foreach ([$game->homeTeam, $game->awayTeam] as $team)
                        <div>
                            <h3 class="font-medium mb-3">{{ $team->name }}</h3>
                            <div class="space-y-2">
                                @forelse ($rosters[$team->id] ?? [] as $row)
                                    @php
                                        $current = $attendance[$row->player_id] ?? null;
                                        $ban = $suspensions[$row->player_id] ?? null;
                                    @endphp
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <input type="hidden" name="rows[{{ $team->id }}_{{ $row->player_id }}][player_id]" value="{{ $row->player_id }}">
                                        <input type="hidden" name="rows[{{ $team->id }}_{{ $row->player_id }}][team_id]" value="{{ $team->id }}">
                                        <span class="col-span-6 text-sm">
                                            {{ $row->player->displayName() }}
                                            @if ($ban)
                                                <span class="text-xs text-rose-600">Sancionado ({{ $ban->matches_remaining }})</span>
                                            @endif
                                        </span>
                                        <select name="rows[{{ $team->id }}_{{ $row->player_id }}][status]" class="field col-span-4 !mt-0">
                                            @foreach (['starter'=>'Titular','substitute'=>'Suplente','present'=>'Presente','absent'=>'Ausente','injured'=>'Lesionado'] as $value => $label)
                                                <option value="{{ $value }}" @selected(($current->status ?? ($ban ? 'absent' : 'present')) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="rows[{{ $team->id }}_{{ $row->player_id }}][minutes_played]" value="{{ $current->minutes_played ?? '' }}" class="field col-span-2 !mt-0" placeholder="min">
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">Sin planilla. Inscribí el equipo al torneo.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="btn-primary mt-6">Guardar asistencia</button>
            </form>
        </section>
    </div>
</x-app-layout>
