@props([
    'games',
    'matchday',
    'tournament' => null,
    'admin' => false,
])

@php
    $sorted = collect($games)->sortBy([
        fn ($g) => optional($g->scheduled_at)->timestamp ?? PHP_INT_MAX,
        fn ($g) => $g->field_name ?? '',
        fn ($g) => $g->id,
    ])->values();

    $slots = $sorted->groupBy(fn ($g) => optional($g->scheduled_at)->format('H:i') ?: 'Sin hora');
    $horarios = $slots->keys()->reject(fn ($h) => $h === 'Sin hora')->values();
    $dateLabel = optional($sorted->first()?->scheduled_at)->format('d/m/Y') ?? 'Sin fecha';
@endphp

<section {{ $attributes->class(['card mb-5 overflow-hidden']) }}>
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div>
            <h3 class="font-semibold">Fecha {{ $matchday }}</h3>
            <p class="text-xs text-slate-500">
                {{ $dateLabel }}
                · {{ $sorted->count() }} {{ $sorted->count() === 1 ? 'partido' : 'partidos' }}
                @if ($horarios->count() > 1)
                    · <span class="font-medium text-arena-navy">{{ $horarios->count() }} turnos:</span>
                    {{ $horarios->implode(' · ') }}
                @elseif ($horarios->count() === 1)
                    · turno {{ $horarios->first() }}
                @endif
            </p>
        </div>
        @if ($admin && $tournament && $sorted->where('status', '!=', 'finished')->count())
            <x-confirm-button
                :action="route('tournaments.postpone-matchday', $tournament)"
                title="¿Aplazar la Fecha {{ $matchday }}?"
                :message="'Se reprograma al próximo '.$tournament->playDaysLabel().' y también se corren las fechas siguientes.'"
                confirm="Sí, aplazar fecha"
                tone="amber"
                class="btn-ghost text-amber-700 border-amber-200"
            >
                <x-slot:form>
                    <input type="hidden" name="matchday" value="{{ $matchday }}">
                    <div>
                        <label class="text-sm text-slate-600">Motivo</label>
                        <input name="reason" value="Postergada por clima / cancha natural" class="field" placeholder="Lluvia, cancha en mal estado...">
                    </div>
                </x-slot:form>
                Aplazar fecha completa
            </x-confirm-button>
        @endif
    </div>

    <div class="divide-y divide-slate-100">
        @foreach ($slots as $hora => $slotGames)
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 bg-arena-mist/80 border-b border-arena-navy/5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 min-w-16 items-center justify-center rounded-xl bg-arena-navy px-3 text-sm font-bold text-white">
                            {{ $hora }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-arena-navy">
                                @if ($hora === 'Sin hora')
                                    Sin horario
                                @else
                                    Turno {{ $hora }}
                                @endif
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $slotGames->count() }} {{ $slotGames->count() === 1 ? 'partido' : 'partidos' }}
                                @php
                                    $canchas = $slotGames->map(fn ($g) => $g->locationLabel())->unique()->values();
                                @endphp
                                @if ($canchas->isNotEmpty())
                                    · {{ $canchas->implode(' · ') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                @if ($admin)
                    <div class="table-shell border-0 rounded-none">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Cancha</th>
                                    <th>Local</th>
                                    <th class="text-center">Marcador</th>
                                    <th>Visitante</th>
                                    <th>Estado</th>
                                    <th>Árbitro</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($slotGames as $game)
                                    <tr>
                                        <td>
                                            {{ optional($game->scheduled_at)->format('H:i') ?? '—' }}
                                            @if ($game->postpone_reason)
                                                <div class="text-xs text-amber-700">{{ $game->postpone_reason }}</div>
                                            @endif
                                            @if ($game->isWalkover())
                                                <div class="text-xs text-amber-700">{{ $game->walkoverReasonLabel() }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $game->locationLabel() }}</td>
                                        <td>
                                            {{ $game->homeTeam->name }}
                                            @if ($tournament && $tournament->isTeamDisqualified($game->home_team_id))
                                                <span class="text-xs text-rose-600">DQ</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-semibold text-arena-navy">{{ $game->scoreline() }}</td>
                                        <td>
                                            {{ $game->awayTeam->name }}
                                            @if ($tournament && $tournament->isTeamDisqualified($game->away_team_id))
                                                <span class="text-xs text-rose-600">DQ</span>
                                            @endif
                                        </td>
                                        <td>{{ $game->statusLabel() }}</td>
                                        <td class="text-sm text-slate-600">{{ $game->refereesLabel() }}</td>
                                        <td><a href="{{ route('games.show', $game) }}" class="text-arena-navy text-sm font-medium">Abrir partido</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="space-y-2 p-4">
                        @foreach ($slotGames as $game)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white px-4 py-3">
                                <div>
                                    <p class="font-medium">{{ $game->homeTeam?->name }} vs {{ $game->awayTeam?->name }}</p>
                                    <p class="text-sm text-slate-500">
                                        {{ optional($game->scheduled_at)->format('d/m/Y H:i') ?? 'Sin horario' }}
                                        · {{ $game->locationLabel() }}
                                        · {{ $game->refereesLabel() }}
                                    </p>
                                </div>
                                <div class="text-sm font-semibold">
                                    @if ($game->status === 'finished')
                                        {{ $game->home_score }} – {{ $game->away_score }}
                                        @if (! empty($game->is_walkover)) <span class="text-rose-600">W.O.</span> @endif
                                    @else
                                        {{ $game->statusLabel() }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>
