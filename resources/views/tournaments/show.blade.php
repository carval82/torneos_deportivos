<x-app-layout>
    <x-slot name="header">{{ $tournament->name }}</x-slot>
    <x-slot name="subheader">{{ $tournament->sport->name }} · {{ $tournament->ageLabel() }} · {{ $tournament->playDaysLabel() }} · {{ $tournament->formatLabel() }}</x-slot>

    @php
        $tabs = [
            'resumen' => 'Resumen',
            'reglamento' => 'Reglamento',
            'fixture' => 'Fixture',
            'sanciones' => 'Sanciones',
            'tabla' => 'Tabla',
            'goleadores' => 'Goleadores',
            'asistencia' => 'Asistencia',
            'curvas' => 'Curvas',
            'probabilidades' => 'Probabilidades',
            'planillas' => 'Planillas',
        ];
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('tournaments.show', ['tournament' => $tournament, 'tab' => $key]) }}"
                   class="{{ $tab === $key ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($tournament->public_slug)
                <a href="{{ route('public.tournaments.show', $tournament->public_slug) }}" class="btn-accent">Vista torneo</a>
            @endif
            @if ($tournament->rules_published)
                <a href="{{ route('tournaments.rules', $tournament) }}" target="_blank" class="btn-ghost">Reglamento</a>
            @endif
            <a href="{{ route('tournaments.edit', $tournament) }}" class="btn-ghost">Editar</a>
            @if ($tournament->games->isEmpty())
                <form method="POST" action="{{ route('tournaments.fixture', $tournament) }}">
                    @csrf
                    <button class="btn-primary">Generar fixture</button>
                </form>
            @else
                <x-confirm-button
                    :action="route('tournaments.fixture.reset', $tournament)"
                    method="DELETE"
                    title="¿Resetear el fixture?"
                    message="Se borran todos los partidos de este torneo. Después podés generarlo de nuevo."
                    confirm="Sí, borrar fixture"
                    tone="danger"
                    class="btn-danger"
                >
                    Resetear fixture
                </x-confirm-button>
            @endif
        </div>
    </div>

    @if ($tab === 'resumen')
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="card p-6 lg:col-span-2">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="font-semibold">Inscribir equipo</h2>
                    <span class="text-sm text-slate-500">{{ $tournament->teams->count() }}{{ $tournament->max_teams ? ' / '.$tournament->max_teams : '' }} equipos</span>
                </div>
                <form method="POST" action="{{ route('tournaments.enroll', $tournament) }}" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <select name="team_id" class="field" required>
                        <option value="">Elegí un equipo</option>
                        @foreach ($availableTeams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn-primary shrink-0">Inscribir</button>
                </form>
                <div class="mt-6 grid sm:grid-cols-2 gap-3">
                    @forelse ($tournament->teams as $team)
                        <div class="rounded-2xl border border-slate-100 px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ route('teams.show', $team) }}" class="font-medium hover:text-arena-limeDark">{{ $team->name }}</a>
                                @if (($team->pivot->status ?? 'active') === 'disqualified')
                                    <span class="text-xs font-medium text-rose-700 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full">Descalificado</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-500">
                                {{ $team->players->count() }} jugadores
                                @if ((int) ($team->pivot->no_show_count ?? 0) > 0)
                                    · {{ $team->pivot->no_show_count }} W.O.
                                @endif
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('tournaments.invites.create', $tournament) }}">
                                    @csrf
                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                    <button class="btn-ghost text-xs px-3 py-1.5">Invitar delegado</button>
                                </form>
                                @if (($activeInvites[$team->id] ?? null))
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-arena-limeDark"
                                        onclick="navigator.clipboard.writeText(@js($activeInvites[$team->id]->url()))"
                                    >Copiar link</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500">Todavía no hay equipos inscriptos.</p>
                    @endforelse
                </div>
            </section>
            <section class="card p-6">
                <h2 class="font-semibold mb-3">Cómo se juega</h2>
                <dl class="space-y-2 text-sm text-slate-600">
                    <div class="flex justify-between gap-3"><dt>Estado</dt><dd>{{ $tournament->statusLabel() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Días</dt><dd>{{ $tournament->playDaysLabel() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Complejo</dt><dd>{{ $tournament->complex_name ?: ($tournament->venue ?: '—') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Canchas</dt><dd>{{ count($tournament->fieldList()) }} · {{ $tournament->fieldSurfaceLabel() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Cada</dt><dd>{{ $tournament->days_between_rounds ?: 7 }} días</dd></div>
                    <div class="flex justify-between gap-3"><dt>W.O.</dt><dd>{{ $competitionRules['walkover_goals_for'] }}-{{ $competitionRules['walkover_goals_against'] }} · DQ a {{ $competitionRules['max_no_shows_before_dq'] }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Edad</dt><dd>{{ $tournament->ageLabel() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Género</dt><dd>{{ $tournament->genderLabel() }}</dd></div>
                </dl>
                @if ($tournament->rules_summary)
                    <p class="mt-4 text-sm text-arena-navy">{{ $tournament->rules_summary }}</p>
                @endif
                <p class="mt-3 text-xs text-slate-500">{{ $competitionNarrative }}</p>
            </section>
        </div>
    @endif

    @if ($tab === 'reglamento')
        <div class="card p-6 max-w-4xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Reglamento del torneo</h2>
                    <p class="text-sm text-slate-500">Los jugadores y equipos deben conocer estas normas.</p>
                </div>
                @if ($tournament->rules_published)
                    <a href="{{ route('tournaments.rules', $tournament) }}" target="_blank" class="btn-primary">Abrir link público</a>
                @else
                    <span class="text-sm text-amber-700">Todavía no está publicado.</span>
                @endif
            </div>
            @if ($tournament->rules_summary)
                <div class="rounded-2xl border border-arena-lime/40 bg-arena-mist px-4 py-3 text-arena-navy">{{ $tournament->rules_summary }}</div>
            @endif
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 text-sm">
                <p class="font-medium mb-1">Reglas automáticas de competencia</p>
                <p>{{ $competitionNarrative }}</p>
            </div>
            <div class="whitespace-pre-wrap text-sm text-slate-600 leading-relaxed">{{ $tournament->rules ?: 'Todavía no cargaste el reglamento. Editá el torneo y escribilas.' }}</div>
        </div>
    @endif

    @if ($tab === 'fixture')
        <div class="mb-4 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $tournament->playDaysLabel() }} ·
            {{ $tournament->fieldSurfaceLabel() }} ·
            cada {{ $tournament->days_between_rounds ?: 7 }} días ·
            {{ implode(', ', $tournament->fieldList()) }}.
            Horarios de cada fecha: <strong>{{ $tournament->timeSlotsLabel() }}</strong>.
            Primero se llenan todas las canchas en el primer turno, después el siguiente.
            Si el clima posterga toda la fecha, usá <strong>Aplazar fecha completa</strong>.
        </div>

        @forelse ($gamesByMatchday as $matchday => $games)
            <x-matchday-schedule
                :games="$games"
                :matchday="$matchday"
                :tournament="$tournament"
                :admin="true"
            />
        @empty
            <div class="card p-8 text-slate-500">Generá el fixture cuando estén los equipos.</div>
        @endforelse
    @endif

    @if ($tab === 'sanciones')
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold">Jugadores sancionados</h2>
                <p class="text-sm text-slate-500">Roja = {{ $tournament->red_ban_matches ?: 1 }} fecha(s). Doble amarilla = {{ $tournament->double_yellow_ban_matches ?: 1 }} fecha(s).</p>
            </div>
            <div class="table-shell border-0 rounded-none">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Jugador</th>
                            <th>Equipo</th>
                            <th>Motivo</th>
                            <th>Restan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suspensions as $suspension)
                            <tr>
                                <td class="font-medium">{{ $suspension->player?->displayName() }}</td>
                                <td>{{ $suspension->team?->name }}</td>
                                <td>{{ $suspension->label() }} · {{ $suspension->reason }}</td>
                                <td>{{ $suspension->matches_remaining }} / {{ $suspension->matches_total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-slate-500">No hay sanciones activas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'tabla')
        <div class="card overflow-hidden">
            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>GF</th><th>GC</th><th>DG</th><th>Pts</th><th>Forma</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($table as $row)
                            <tr class="{{ ! empty($row->disqualified) ? 'opacity-60' : '' }}">
                                <td>{{ $row->position ?? '—' }}</td>
                                <td class="font-medium">
                                    {{ $row->team->name }}
                                    @if (! empty($row->disqualified))
                                        <span class="text-xs text-rose-600 font-normal">Descalificado</span>
                                    @endif
                                </td>
                                <td>{{ $row->played }}</td>
                                <td>{{ $row->won }}</td>
                                <td>{{ $row->drawn }}</td>
                                <td>{{ $row->lost }}</td>
                                <td>{{ $row->goals_for }}</td>
                                <td>{{ $row->goals_against }}</td>
                                <td>{{ $row->goal_difference }}</td>
                                <td class="font-bold text-arena-navy">{{ $row->points }}</td>
                                <td class="tracking-widest text-xs">{{ implode(' ', $row->form) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'goleadores')
        <div class="card overflow-hidden">
            <div class="table-shell">
                <table class="data-table">
                    <thead><tr><th>#</th><th>Jugador</th><th>Equipo</th><th>{{ $tournament->sport->scoring_unit }}</th></tr></thead>
                    <tbody>
                        @forelse ($scorers as $row)
                            <tr>
                                <td>{{ $row->position }}</td>
                                <td><a href="{{ route('players.show', $row->player) }}" class="text-arena-navy">{{ $row->player?->displayName() }}</a></td>
                                <td>{{ $row->team?->name }}</td>
                                <td class="font-semibold">{{ $row->goals }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-slate-500">Cargá goles en los partidos para armar esta tabla.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'asistencia')
        <div class="card overflow-hidden">
            <div class="table-shell">
                <table class="data-table">
                    <thead><tr><th>Jugador</th><th>Presente</th><th>Ausente</th><th>%</th></tr></thead>
                    <tbody>
                        @forelse ($attendance as $row)
                            <tr>
                                <td>{{ $row->player?->displayName() }}</td>
                                <td>{{ $row->present }}</td>
                                <td>{{ $row->absent }}</td>
                                <td>{{ $row->rate }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-slate-500">La asistencia se carga en cada partido.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'curvas')
        <div class="card p-6">
            <h2 class="font-semibold mb-2">Curva de rendimiento</h2>
            <p class="text-sm text-slate-500 mb-6">Posición en la tabla después de cada fecha. El 1 queda arriba.</p>
            <canvas id="curvesChart" height="120"></canvas>
        </div>
        @push('head')
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        @endpush
        @push('scripts')
            <script>
                const curves = @json($curves);
                const colors = ['#34d399','#38bdf8','#fbbf24','#f472b6','#a78bfa','#fb7185','#2dd4bf','#f97316'];
                new Chart(document.getElementById('curvesChart'), {
                    type: 'line',
                    data: {
                        labels: curves.labels.map(n => 'F' + n),
                        datasets: curves.series.map((s, i) => ({
                            label: s.name,
                            data: s.positions,
                            borderColor: s.color || colors[i % colors.length],
                            backgroundColor: 'transparent',
                            tension: 0.25,
                            spanGaps: true,
                        }))
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { reverse: true, min: 1, ticks: { stepSize: 1, color: '#64748b' }, grid: { color: 'rgba(15,23,42,.08)' } },
                            x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(15,23,42,.05)' } }
                        },
                        plugins: { legend: { labels: { color: '#334155' } } }
                    }
                });
            </script>
        @endpush
    @endif

    @if ($tab === 'probabilidades')
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="card p-6">
                <h2 class="font-semibold mb-4">Chance de título</h2>
                <div class="space-y-3">
                    @foreach ($titleOdds as $row)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>{{ $row->team->name }}</span>
                                <span class="text-arena-navy">{{ $row->title_probability }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-arena-limeDark" style="width: {{ $row->title_probability }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="card p-6">
                <h2 class="font-semibold mb-4">Próximos partidos</h2>
                <div class="space-y-4">
                    @forelse ($tournament->games->where('status', '!=', 'finished')->take(6) as $game)
                        <div class="rounded-2xl border border-slate-100 p-4">
                            <p class="font-medium">{{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }}</p>
                            @if (isset($matchOdds[$game->id]))
                                <p class="text-xs text-slate-500 mt-2">
                                    Local {{ $matchOdds[$game->id]['home_win'] }}% ·
                                    Empate {{ $matchOdds[$game->id]['draw'] }}% ·
                                    Visita {{ $matchOdds[$game->id]['away_win'] }}%
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500">No quedan partidos por jugar.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    @if ($tab === 'planillas')
        <div class="space-y-6">
            @foreach ($tournament->teams as $team)
                <section class="card p-6">
                    <h2 class="font-semibold mb-4">{{ $team->name }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($team->players as $player)
                            @php $check = $eligibility[$player->id] ?? null; @endphp
                            <a href="{{ route('players.show', ['player' => $player, 'tournament_id' => $tournament->id]) }}" class="rounded-2xl border border-slate-100 p-4 hover:bg-slate-50">
                                <div class="flex gap-3">
                                    <div class="h-14 w-14 rounded-2xl bg-slate-50 overflow-hidden shrink-0">
                                        @if ($player->photoUrl())
                                            <img src="{{ $player->photoUrl() }}" class="h-full w-full object-cover" alt="">
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ $player->displayName() }}</p>
                                        <p class="text-xs text-slate-500">{{ $player->document_type }} {{ $player->document_number }} · {{ $check['age'] ?? '—' }} años</p>
                                        @if ($check && ! $check['eligible'])
                                            <p class="text-xs text-rose-600 mt-1">No habilita: {{ $check['reason'] }}</p>
                                        @elseif ($check && count($check['warnings']))
                                            <p class="text-xs text-amber-700 mt-1">{{ $check['warnings'][0] }}</p>
                                        @else
                                            <p class="text-xs text-arena-navy mt-1">Habilitado</p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-app-layout>
