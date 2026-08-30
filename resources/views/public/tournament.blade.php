@extends('layouts.public')

@section('title', $tournament->name.' · Arena Players')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-arena-limeDark">{{ $tournament->sport?->name }} · {{ $tournament->ageLabel() }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl font-semibold">{{ $tournament->name }}</h1>
        <p class="mt-2 text-slate-600">{{ $tournament->statusLabel() }} · {{ $tournament->playDaysLabel() }} · {{ $tournament->complex_name ?: ($tournament->venue ?: 'Sede a confirmar') }}</p>
    </div>

    <nav class="flex flex-wrap gap-2 mb-8">
        @foreach ([
            ['home', 'Inicio', route('public.tournaments.show', $tournament->public_slug)],
            ['fixture', 'Fixture', route('public.tournaments.fixture', $tournament->public_slug)],
            ['tabla', 'Tabla', route('public.tournaments.standings', $tournament->public_slug)],
            ['goleadores', 'Goleadores', route('public.tournaments.scorers', $tournament->public_slug)],
            ['reglamento', 'Reglamento', route('public.tournaments.rules', $tournament->public_slug)],
        ] as [$key, $label, $url])
            <a href="{{ $url }}"
               class="{{ ($section ?? '') === $key ? 'btn-accent' : 'btn-ghost' }} text-sm">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if (($section ?? '') === 'home')
        <div class="grid lg:grid-cols-2 gap-6">
            <section class="card p-6">
                <h2 class="font-semibold mb-4">Próximos partidos</h2>
                <div class="space-y-3">
                    @forelse ($upcoming as $game)
                        <div class="rounded-xl border border-slate-100 px-4 py-3">
                            <p class="text-xs text-slate-500">Fecha {{ $game->matchday }} · {{ optional($game->scheduled_at)->format('d/m H:i') }}</p>
                            <p class="font-medium mt-1">{{ $game->homeTeam?->name }} vs {{ $game->awayTeam?->name }}</p>
                        </div>
                    @empty
                        <p class="text-slate-500">Sin partidos pendientes.</p>
                    @endforelse
                </div>
            </section>
            <section class="card p-6">
                <h2 class="font-semibold mb-4">Tabla (top 8)</h2>
                <div class="table-shell">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Equipo</th>
                                <th>Pts</th>
                                <th>PJ</th>
                                <th>DG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($table as $row)
                                <tr>
                                    <td>{{ $row->position ?? '—' }}</td>
                                    <td>{{ $row->team?->name }}</td>
                                    <td>{{ $row->points }}</td>
                                    <td>{{ $row->played }}</td>
                                    <td>{{ $row->goal_difference }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif

    @if (($section ?? '') === 'fixture')
        <div class="space-y-6">
            @forelse (($gamesByMatchday ?? collect()) as $matchday => $games)
                <section class="card p-6">
                    <h2 class="font-semibold mb-4">Fecha {{ $matchday }}</h2>
                    <div class="space-y-3">
                        @foreach ($games as $game)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 px-4 py-3">
                                <div>
                                    <p class="font-medium">{{ $game->homeTeam?->name }} vs {{ $game->awayTeam?->name }}</p>
                                    <p class="text-sm text-slate-500">{{ optional($game->scheduled_at)->format('d/m/Y H:i') }} · {{ $game->field ?: 'Cancha' }}</p>
                                </div>
                                <div class="text-sm font-semibold">
                                    @if ($game->status === 'finished')
                                        {{ $game->home_score }} – {{ $game->away_score }}
                                        @if (!empty($game->is_walkover)) <span class="text-rose-600">W.O.</span> @endif
                                    @else
                                        Tentativo
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="card p-8 text-slate-500">Fixture aún no publicado.</div>
            @endforelse
        </div>
    @endif

    @if (($section ?? '') === 'tabla')
        <section class="card p-6">
            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Equipo</th>
                            <th>Pts</th>
                            <th>PJ</th>
                            <th>G</th>
                            <th>E</th>
                            <th>P</th>
                            <th>GF</th>
                            <th>GC</th>
                            <th>DG</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($table as $row)
                            <tr>
                                <td>{{ $row->position ?? '—' }}</td>
                                <td>{{ $row->team?->name }}</td>
                                <td class="font-semibold">{{ $row->points }}</td>
                                <td>{{ $row->played }}</td>
                                <td>{{ $row->won }}</td>
                                <td>{{ $row->drawn }}</td>
                                <td>{{ $row->lost }}</td>
                                <td>{{ $row->goals_for }}</td>
                                <td>{{ $row->goals_against }}</td>
                                <td>{{ $row->goal_difference }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if (($section ?? '') === 'goleadores')
        <section class="card p-6">
            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Jugador</th>
                            <th>Equipo</th>
                            <th>Goles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scorers as $row)
                            <tr>
                                <td>{{ $row->position }}</td>
                                <td>{{ $row->player?->displayName() }}</td>
                                <td>{{ $row->team?->name }}</td>
                                <td class="font-semibold">{{ $row->goals }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-slate-500">Todavía no hay goles cargados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if (($section ?? '') === 'reglamento')
        <section class="card p-6 space-y-4">
            @if ($tournament->rules_summary)
                <p class="rounded-xl bg-arena-mist px-4 py-3 text-sm">{{ $tournament->rules_summary }}</p>
            @endif
            @isset($competitionNarrative)
                <p class="text-sm text-slate-600">{{ $competitionNarrative }}</p>
            @endisset
            <div class="prose prose-sm max-w-none whitespace-pre-line text-slate-700">{{ $tournament->rules }}</div>
        </section>
    @endif
@endsection
