<x-app-layout>
    <x-slot name="header">{{ $tournament->name }}</x-slot>
    <x-slot name="subheader">{{ $tournament->sport->name }} · {{ $tournament->ageLabel() }} · {{ $tournament->playDaysLabel() }} · {{ $tournament->formatLabel() }}</x-slot>

    @if ($tournament->lockReason())
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $tournament->lockReason() }}
            @if ($tournament->isFrozen() || ($tournament->hasEnded() && ! $tournament->hasUnfinishedGames()))
                <a href="{{ route('billing.index') }}" class="font-semibold underline">Activación / renovar</a>
            @endif
        </div>
    @endif

    @php
        $tabs = [
            'resumen' => 'Resumen',
            'delegados' => 'Delegados',
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
            @if ($canManage ?? false)
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
                @if ($canManage ?? false)
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
                @endif
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
                            @if ($team->delegates->isNotEmpty())
                                <div class="mt-2 space-y-1">
                                    @foreach ($team->delegates as $delegate)
                                        <p class="text-xs text-slate-600">
                                            {{ $delegate->name }} · {{ $delegate->email }}
                                            @if ($delegate->pivot->is_disciplinary_committee)
                                                <span class="text-amber-700 font-semibold">· Comité</span>
                                            @endif
                                        </p>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-2 text-xs text-slate-400">Sin delegado</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <a href="{{ route('tournaments.show', ['tournament' => $tournament, 'tab' => 'delegados']) }}" class="btn-ghost text-xs px-3 py-1.5">
                                    Asignar delegado
                                </a>
                                <form method="POST" action="{{ route('tournaments.invites.create', $tournament) }}">
                                    @csrf
                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                    <button class="btn-ghost text-xs px-3 py-1.5">Link de invitación</button>
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
                    <div class="flex justify-between gap-3"><dt>Árbitros</dt><dd>{{ ($competitionRules['referee_crew'] ?? 'single') === 'trio' ? 'Terna (3)' : 'Uno por partido' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Coord. arbitral</dt><dd>{{ $tournament->refereeCoordinator?->name ?: 'Sin asignar' }}</dd></div>
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

    @if ($tab === 'delegados')
        <div class="grid gap-6 lg:grid-cols-5">
            <section class="card p-6 lg:col-span-2 h-fit">
                <h2 class="font-semibold text-lg">Crear delegado y vincularlo</h2>
                <p class="text-sm text-slate-500 mt-1 mb-5">
                    Podés usar un equipo ya cargado o crear uno nuevo. Si no está en este torneo, se inscribe al guardar.
                </p>
                <form method="POST" action="{{ route('tournaments.delegates.store', $tournament) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm text-slate-600">Equipo existente</label>
                        <select name="team_id" class="field">
                            <option value="">Elegí equipo (o creá uno abajo)</option>
                            @foreach ($allTeams as $team)
                                <option value="{{ $team->id }}" @selected((int) old('team_id') === (int) $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">O crear equipo nuevo</label>
                        <input name="new_team_name" value="{{ old('new_team_name') }}" class="field" placeholder="Ej. Las Tekas">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Nombre del delegado</label>
                        <input name="name" value="{{ old('name') }}" class="field" required placeholder="Juan Pérez">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Correo</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="field" required placeholder="delegado@club.com">
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
                            <input name="document_number" value="{{ old('document_number') }}" class="field" required>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_disciplinary_committee" value="1" class="rounded border-slate-300">
                        Pertenece al comité disciplinario
                    </label>
                    <button class="btn-primary w-full">Crear y vincular</button>
                </form>
            </section>

            <section class="card p-6 lg:col-span-3">
                <h2 class="font-semibold text-lg mb-4">Equipos y delegados de este torneo</h2>
                @forelse ($tournament->teams as $team)
                    <div class="rounded-2xl border border-slate-100 px-4 py-3 mb-3">
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ route('teams.show', $team) }}" class="font-semibold text-arena-navy hover:underline">{{ $team->name }}</a>
                            <span class="text-xs text-slate-500">{{ $team->players->count() }} jugadores</span>
                        </div>
                        @forelse ($team->delegates as $delegate)
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-slate-600">
                                    {{ $delegate->name }} · {{ $delegate->email }}
                                    · {{ $delegate->document_type }} {{ $delegate->document_number }}
                                    @if ($delegate->pivot->is_disciplinary_committee)
                                        <span class="text-amber-700 font-semibold">· Comité</span>
                                    @endif
                                </p>
                                <form method="POST" action="{{ route('tournaments.delegates.committee', [$tournament, $delegate]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                                    <input type="hidden" name="is_disciplinary_committee" value="{{ $delegate->pivot->is_disciplinary_committee ? 0 : 1 }}">
                                    <button class="text-xs font-semibold text-arena-limeDark">
                                        {{ $delegate->pivot->is_disciplinary_committee ? 'Quitar del comité' : 'Marcar comité' }}
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400 mt-2">Este equipo todavía no tiene delegado.</p>
                        @endforelse
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-500">
                        Todavía no hay equipos. Creá uno con el formulario de la izquierda al cargar el delegado.
                    </div>
                @endforelse
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
            <p class="font-medium mb-1">Dos tipos de aplazo</p>
            <p>
                <strong>Un partido:</strong> se mueve solo ese encuentro (lluvia en una cancha, se quedó sin luz, etc.). El resto de la fecha sigue.
                <strong>Fecha completa:</strong> corre todos los pendientes de esa jornada y también las fechas siguientes.
            </p>
            <p class="mt-2">
                {{ $tournament->playDaysLabel() }} ·
                {{ $tournament->fieldSurfaceLabel() }} ·
                horarios {{ $tournament->timeSlotsLabel() }}.
                Podés generar el fixture automático o cargarlo a mano, partido por partido.
            </p>
        </div>

        @if ($canManage ?? false)
            <section class="card p-6 mb-5">
                <h2 class="font-semibold mb-1">Agregar partido a mano</h2>
                <p class="text-sm text-slate-500 mb-4">El organizador arma el calendario. No hace falta generar el automático.</p>
                <form method="POST" action="{{ route('tournaments.games.store', $tournament) }}" class="grid gap-3 md:grid-cols-2 lg:grid-cols-6">
                    @csrf
                    <div>
                        <label class="text-xs text-slate-500">Local</label>
                        <select name="home_team_id" class="field" required>
                            <option value="">Equipo</option>
                            @foreach ($tournament->teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Visitante</label>
                        <select name="away_team_id" class="field" required>
                            <option value="">Equipo</option>
                            @foreach ($tournament->teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Fecha N°</label>
                        <input type="number" name="matchday" min="1" max="80" value="{{ old('matchday', ($gamesByMatchday->keys()->max() ?: 0) + 1) }}" class="field" required>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Día y hora</label>
                        <input type="datetime-local" name="scheduled_at" class="field" required>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Cancha</label>
                        <select name="field_name" class="field">
                            @foreach ($tournament->fieldList() as $field)
                                <option value="{{ $field }}">{{ $field }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="btn-primary w-full">Agregar</button>
                    </div>
                </form>
            </section>
        @endif

        @forelse ($gamesByMatchday as $matchday => $games)
            <x-matchday-schedule
                :games="$games"
                :matchday="$matchday"
                :tournament="$tournament"
                :admin="$canManage ?? false"
                :can-schedule="$canSchedule ?? false"
            />
        @empty
            <div class="card p-8 text-slate-500">
                Todavía no hay partidos.
                @if ($canManage ?? false)
                    Cargá uno a mano arriba o usá <strong>Generar fixture</strong>.
                @endif
            </div>
        @endforelse
    @endif

    @if ($tab === 'sanciones')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="font-semibold">Jugadores sancionados</h2>
                        <p class="text-sm text-slate-500">Roja = {{ $tournament->red_ban_matches ?: 1 }} fecha(s). Doble amarilla = {{ $tournament->double_yellow_ban_matches ?: 1 }} fecha(s). El comité también puede emitir sentencias.</p>
                    </div>
                    <div class="table-shell border-0 rounded-none">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Jugador</th>
                                    <th>Equipo</th>
                                    <th>Motivo</th>
                                    <th>Restan</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($suspensions as $suspension)
                                    <tr>
                                        <td class="font-medium">{{ $suspension->player?->displayName() }}</td>
                                        <td>{{ $suspension->team?->name }}</td>
                                        <td>{{ $suspension->label() }} · {{ $suspension->reason }}</td>
                                        <td>{{ $suspension->matches_remaining }} / {{ $suspension->matches_total }}</td>
                                        <td>
                                            @if ($canDiscipline && $suspension->source === 'committee')
                                                <form method="POST" action="{{ route('sentences.revoke', $suspension) }}">
                                                    @csrf
                                                    <button class="text-xs text-rose-700 font-semibold">Revocar</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-slate-500">No hay sanciones activas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if (auth()->user()?->isAdmin() && $pendingExceptions->isNotEmpty())
                    <div class="card p-5">
                        <h2 class="font-semibold mb-3">Excepciones de edad pendientes</h2>
                        <div class="space-y-3">
                            @foreach ($pendingExceptions as $exception)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p class="font-medium">{{ $exception->player?->displayName() }} · {{ $exception->team?->name }}</p>
                                    <p class="text-sm text-slate-600 mt-1">{{ $exception->reason }}</p>
                                    <form method="POST" action="{{ route('exceptions.review', $exception) }}" class="mt-3 flex flex-wrap gap-2">
                                        @csrf
                                        <input name="review_notes" class="field" placeholder="Nota del master (opcional)">
                                        <button name="status" value="approved" class="btn-primary text-xs">Aprobar</button>
                                        <button name="status" value="rejected" class="btn-ghost text-xs text-rose-700">Rechazar</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($canDiscipline)
                <section class="card p-5 h-fit">
                    <h2 class="font-semibold mb-2">Emitir sentencia</h2>
                    <p class="text-xs text-slate-500 mb-4">Comité disciplinario / organizador / master.</p>
                    <form method="POST" action="{{ route('sentences.store', $tournament) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-sm text-slate-600">Jugador</label>
                            <select name="player_id" class="field" required>
                                <option value="">Elegí jugador</option>
                                @foreach ($tournament->teams as $team)
                                    @foreach ($team->players as $player)
                                        <option value="{{ $player->id }}">{{ $team->short_name ?: $team->name }} · {{ $player->displayName() }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Fechas de sanción</label>
                            <input type="number" name="matches" min="1" max="20" value="1" class="field" required>
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Motivo</label>
                            <input name="reason" class="field" placeholder="Agresión, insultos, etc." required>
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Detalle</label>
                            <textarea name="notes" rows="3" class="field" placeholder="Fundamento de la sentencia"></textarea>
                        </div>
                        <button class="btn-primary w-full">Registrar sentencia</button>
                    </form>
                </section>
            @endif
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
