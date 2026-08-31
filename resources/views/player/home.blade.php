<x-app-layout>
    <x-slot name="header">Hola, {{ $player->first_name }}</x-slot>
    <x-slot name="subheader">Cédula {{ $player->document_number }} · {{ $player->team?->name ?? 'Sin equipo' }}</x-slot>

    <section class="card p-6 mb-6 border-arena-lime/30 bg-arena-mist/60">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-arena-limeDark">Documento verificado</p>
        <div class="mt-3 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-slate-500">Jugador</p>
                <p class="font-semibold text-arena-navy">{{ $player->displayName() }}</p>
            </div>
            <div>
                <p class="text-slate-500">Cédula</p>
                <p class="font-semibold text-arena-navy">{{ $player->document_number }}</p>
            </div>
            <div>
                <p class="text-slate-500">Equipo</p>
                <p class="font-semibold text-arena-navy">{{ $player->team?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-slate-500">Torneos vinculados</p>
                <p class="font-semibold text-arena-navy">{{ $tournaments->count() }}</p>
            </div>
        </div>
        @if ($tournaments->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($tournaments as $tournament)
                    <span class="soft-chip">
                        {{ $tournament->name }}
                        @if ($tournament->sport)
                            · {{ $tournament->sport->name }}
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
    </section>

    @if ($tournaments->isEmpty())
        <div class="card p-8 text-slate-600">
            Tu cédula está registrada, pero todavía no estás en la plantilla de ningún torneo.
            Pedile al delegado o al organizador que te sume.
        </div>
    @else
        <div class="grid gap-6 xl:grid-cols-3">
            <section class="card p-6 xl:col-span-2">
                <h2 class="font-semibold mb-4">Próximas fechas de tu equipo</h2>
                <div class="space-y-3">
                    @forelse ($upcoming as $game)
                        <div class="rounded-2xl border border-slate-100 px-4 py-3">
                            <p class="text-xs text-slate-500">{{ $game->tournament?->name }} · Fecha {{ $game->matchday }}</p>
                            <p class="font-medium mt-1">{{ $game->homeTeam?->name }} vs {{ $game->awayTeam?->name }}</p>
                            <p class="text-sm text-slate-500">{{ optional($game->scheduled_at)->format('d/m/Y H:i') }} · {{ $game->locationLabel() }}</p>
                        </div>
                    @empty
                        <p class="text-slate-500">No hay partidos pendientes en tus torneos.</p>
                    @endforelse
                </div>

                <h2 class="font-semibold mt-8 mb-4">Últimos resultados</h2>
                <div class="space-y-3">
                    @forelse ($results as $game)
                        <div class="rounded-2xl border border-slate-100 px-4 py-3 flex justify-between gap-3">
                            <div>
                                <p class="text-xs text-slate-500">{{ $game->tournament?->name }}</p>
                                <p class="font-medium">{{ $game->homeTeam?->short_name ?: $game->homeTeam?->name }} vs {{ $game->awayTeam?->short_name ?: $game->awayTeam?->name }}</p>
                            </div>
                            <p class="font-semibold">{{ $game->home_score }} – {{ $game->away_score }}</p>
                        </div>
                    @empty
                        <p class="text-slate-500">Todavía no hay resultados.</p>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-6">
                <section class="card p-6">
                    <h2 class="font-semibold mb-3">Mi ficha</h2>
                    <dl class="space-y-2 text-sm text-slate-600">
                        <div class="flex justify-between gap-3"><dt>Cédula</dt><dd>{{ $player->document_number }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Equipo</dt><dd>{{ $player->team?->name }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Camiseta</dt><dd>#{{ $player->jersey_number ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Posición</dt><dd>{{ $player->position ?: '—' }}</dd></div>
                    </dl>
                </section>

                @foreach ($tournaments as $tournament)
                    @php $mine = $teamStanding[$tournament->id] ?? null; @endphp
                    <section class="card p-6">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <h2 class="font-semibold">{{ $tournament->name }}</h2>
                            @if ($tournament->public_slug && $tournament->is_public)
                                <a href="{{ route('public.tournaments.show', $tournament->public_slug) }}" class="text-xs font-semibold text-arena-limeDark">Ver público</a>
                            @endif
                        </div>
                        @if ($mine)
                            <p class="text-sm text-slate-600 mb-3">
                                Tu equipo: <strong>{{ $mine->position ?? '—' }}°</strong>
                                · {{ $mine->points }} pts
                                · PJ {{ $mine->played }}
                                · DG {{ $mine->goal_difference }}
                            </p>
                        @endif
                        <div class="space-y-2 text-sm">
                            @foreach (($tables[$tournament->id] ?? collect())->take(6) as $row)
                                <div class="flex justify-between gap-2 {{ $row->team_id === $player->team_id ? 'font-semibold text-arena-navy' : 'text-slate-600' }}">
                                    <span>{{ $row->position ?? '—' }}. {{ $row->team?->short_name ?: $row->team?->name }}</span>
                                    <span>{{ $row->points }} pts</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </aside>
        </div>
    @endif
</x-app-layout>
