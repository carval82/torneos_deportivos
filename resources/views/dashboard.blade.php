<x-app-layout>
    <x-slot name="header">Tablero</x-slot>
    <x-slot name="subheader">Torneos, planteles, goles y curvas en un solo lugar.</x-slot>

    <section class="card mb-8 overflow-hidden border-arena-navy/10">
        <div class="grid lg:grid-cols-[1.1fr_0.9fr]">
            <div class="p-6 sm:p-8 bg-gradient-to-br from-arena-navy via-arena-ink to-arena-navy text-white">
                <p class="text-[11px] font-bold tracking-[0.2em] text-arena-lime uppercase">Arena Players</p>
                <h2 class="mt-2 text-2xl sm:text-3xl font-semibold leading-tight">Centro de control deportivo</h2>
                <p class="mt-3 text-sm text-white/75 max-w-lg">
                    Fixture, planillas, W.O., sanciones y estadísticas. Torneos · Eventos · Pasión que nos une.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('tournaments.create') }}" class="btn-accent">Nuevo torneo</a>
                    <a href="{{ route('tournaments.index') }}" class="btn border border-white/20 bg-white/10 text-white hover:bg-white/15">Ver torneos</a>
                </div>
            </div>
            <div class="p-6 sm:p-8 flex items-center justify-center bg-white">
                <img
                    src="{{ asset('images/brand/logo-320.png') }}"
                    alt="Arena Players"
                    class="h-40 sm:h-48 w-auto object-contain"
                >
            </div>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">
        @foreach ([
            ['Torneos', $stats['tournaments']],
            ['Equipos', $stats['teams']],
            ['Jugadores', $stats['players']],
            ['Partidos', $stats['games']],
        ] as $stat)
            <div class="card p-5 relative overflow-hidden">
                <span class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-arena-lime/20"></span>
                <p class="text-sm text-slate-500 relative">{{ $stat[0] }}</p>
                <p class="mt-2 text-3xl font-semibold text-arena-navy relative">{{ $stat[1] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-arena-navy">Torneos recientes</h2>
                <a href="{{ route('tournaments.create') }}" class="btn-primary">Nuevo torneo</a>
            </div>
            <div class="space-y-3">
                @forelse ($activeTournaments as $tournament)
                    <a href="{{ route('tournaments.show', $tournament) }}" class="flex items-center justify-between rounded-2xl border border-slate-100 px-4 py-3 hover:bg-arena-mist">
                        <div>
                            <p class="font-medium text-arena-navy">{{ $tournament->name }}</p>
                            <p class="text-sm text-slate-500">{{ $tournament->sport->name }} · {{ $tournament->ageLabel() }} · {{ $tournament->playDaysLabel() }} · {{ $tournament->statusLabel() }}</p>
                        </div>
                        <span class="text-arena-limeDark text-sm font-semibold">Abrir</span>
                    </a>
                @empty
                    <p class="text-slate-500">Todavía no hay torneos. Creá el primero y armá el fixture.</p>
                @endforelse
            </div>
        </section>

        <section class="card p-6">
            <h2 class="text-lg font-semibold mb-4 text-arena-navy">Deportes</h2>
            <div class="space-y-3">
                @foreach ($sports as $sport)
                    <div class="flex items-center justify-between rounded-2xl bg-arena-mist px-4 py-3">
                        <div>
                            <p class="font-medium text-arena-navy">{{ $sport->icon }} {{ $sport->name }}</p>
                            <p class="text-xs text-slate-500">Cuenta {{ $sport->scoring_unit }}</p>
                        </div>
                        <span class="text-sm font-semibold text-arena-navy">{{ $sport->tournaments_count }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2 mt-6">
        <section class="card p-6">
            <h2 class="text-lg font-semibold mb-4 text-arena-navy">Próximos partidos</h2>
            <div class="space-y-3">
                @forelse ($upcomingGames as $game)
                    <a href="{{ route('games.show', $game) }}" class="block rounded-2xl border border-slate-100 px-4 py-3 hover:bg-arena-mist">
                        <p class="text-xs text-slate-500">{{ $game->tournament->name }} · {{ $game->round_name }}</p>
                        <p class="font-medium mt-1 text-arena-navy">{{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }}</p>
                        <p class="text-sm text-slate-500">{{ optional($game->scheduled_at)->format('d/m H:i') }} · {{ $game->statusLabel() }}</p>
                    </a>
                @empty
                    <p class="text-slate-500">No hay partidos pendientes.</p>
                @endforelse
            </div>
        </section>
        <section class="card p-6">
            <h2 class="text-lg font-semibold mb-4 text-arena-navy">Últimos resultados</h2>
            <div class="space-y-3">
                @forelse ($recentResults as $game)
                    <a href="{{ route('games.show', $game) }}" class="flex items-center justify-between rounded-2xl border border-slate-100 px-4 py-3 hover:bg-arena-mist">
                        <span class="text-arena-navy">{{ $game->homeTeam->short_name ?? $game->homeTeam->name }}</span>
                        <span class="font-bold text-arena-navy">{{ $game->scoreline() }}</span>
                        <span class="text-arena-navy">{{ $game->awayTeam->short_name ?? $game->awayTeam->name }}</span>
                    </a>
                @empty
                    <p class="text-slate-500">Todavía no hay resultados cargados.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
