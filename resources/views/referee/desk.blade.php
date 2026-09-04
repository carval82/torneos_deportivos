<x-app-layout>
    <x-slot name="header">Mesa arbitral</x-slot>
    <x-slot name="subheader">
        @if (auth()->user()->isRefereeCoordinator())
            Asigná el cuerpo arbitral a cada encuentro y cargá el marcador en vivo.
        @else
            Tus partidos asignados. Podés editar el resultado mientras el encuentro está en juego.
        @endif
    </x-slot>

    @if (auth()->user()->isOrganizer() || auth()->user()->isRefereeCoordinator() || auth()->user()->isAdmin())
        <p class="mb-6 text-sm text-slate-600">
            ¿Falta un árbitro?
            <a href="{{ route('organizer.referees.index') }}" class="font-semibold text-arena-navy underline">Crear árbitros</a>
        </p>
    @endif

    <section class="mb-8">
        <h2 class="font-semibold text-lg mb-3">Mis partidos</h2>
        @forelse ($assigned as $game)
            <a href="{{ route('games.show', $game) }}" class="card p-4 mb-3 block hover:border-arena-navy/20">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-slate-500">{{ $game->tournament?->name }} · {{ $game->round_name }}</p>
                        <p class="font-semibold text-arena-navy">{{ $game->homeTeam?->name }} vs {{ $game->awayTeam?->name }}</p>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ optional($game->scheduled_at)->format('d/m H:i') ?? 'Sin hora' }}
                            · {{ $game->locationLabel() }}
                            · {{ $game->refereesLabel() }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-black text-arena-navy">{{ $game->scoreline() }}</p>
                        <p class="text-xs text-slate-500">{{ $game->statusLabel() }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="card p-8 text-slate-500">No tenés partidos asignados todavía.</div>
        @endforelse
    </section>

    @if ($coordinated->isNotEmpty())
        <section class="mb-8">
            <h2 class="font-semibold text-lg mb-3">Encuentros del torneo</h2>
            <p class="text-sm text-slate-500 mb-4">Asigná 1 árbitro o terna según el reglamento de cada torneo.</p>
            <div class="space-y-3">
                @foreach ($coordinated as $game)
                    <div class="card p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                            <div>
                                <p class="text-xs text-slate-500">{{ $game->tournament?->name }} · Fecha {{ $game->matchday }}</p>
                                <p class="font-semibold">{{ $game->homeTeam?->name }} vs {{ $game->awayTeam?->name }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ optional($game->scheduled_at)->format('d/m H:i') ?? 'Sin hora' }}
                                    · {{ $game->locationLabel() }}
                                </p>
                            </div>
                            <a href="{{ route('games.show', $game) }}" class="text-sm font-semibold text-arena-navy">Abrir planilla</a>
                        </div>
                        @if (auth()->user()->canAssignReferees($game->tournament))
                            <form method="POST" action="{{ route('games.referees.assign', $game) }}" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                @csrf
                                @foreach ($refereeService->duties($game->tournament) as $duty => $label)
                                    <div>
                                        <label class="text-xs text-slate-500">{{ $label }}</label>
                                        <select name="{{ $duty }}" class="field !mt-0">
                                            <option value="">Sin asignar</option>
                                            @foreach ($officials as $official)
                                                <option value="{{ $official->id }}" @selected((int) optional($game->refereeOnDuty($duty))->id === (int) $official->id)>
                                                    {{ $official->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                                <div class="flex items-end">
                                    <button class="btn-primary w-full">Asignar</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($recent->isNotEmpty())
        <section>
            <h2 class="font-semibold text-lg mb-3">Recientes</h2>
            @foreach ($recent as $game)
                <a href="{{ route('games.show', $game) }}" class="flex items-center justify-between rounded-2xl border border-slate-100 px-4 py-3 mb-2 hover:bg-slate-50">
                    <span class="text-sm">{{ $game->homeTeam?->name }} vs {{ $game->awayTeam?->name }}</span>
                    <span class="font-semibold">{{ $game->scoreline() }}</span>
                </a>
            @endforeach
        </section>
    @endif
</x-app-layout>
