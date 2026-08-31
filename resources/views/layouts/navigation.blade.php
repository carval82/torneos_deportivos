@php
    $user = Auth::user();
    $links = [];

    if ($user->isOrganizer() || $user->isAdmin()) {
        $links = [
            ['route' => 'dashboard', 'label' => 'Tablero', 'match' => 'dashboard'],
            ['route' => 'tournaments.index', 'label' => 'Torneos', 'match' => 'tournaments.*'],
            ['route' => 'teams.index', 'label' => 'Equipos', 'match' => 'teams.*'],
            ['route' => 'players.index', 'label' => 'Jugadores', 'match' => 'players.*'],
            ['route' => 'organizer.delegates.index', 'label' => 'Delegados', 'match' => 'organizer.delegates.*'],
            ['route' => 'billing.index', 'label' => $user->isMaster() ? 'Pagos master' : 'Activación', 'match' => 'billing.*'],
        ];
    } elseif ($user->role === 'delegate') {
        $links = [
            ['route' => 'delegate.index', 'label' => 'Mis equipos', 'match' => 'delegate.*'],
        ];
    } elseif ($user->isPlayer()) {
        $links = [
            ['route' => 'player.home', 'label' => 'Mi equipo', 'match' => 'player.*'],
        ];
    }
@endphp

<aside x-data="{ open: false }" class="lg:w-72 shrink-0">
    <div class="lg:hidden flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-white">
        <x-brand variant="mark" size="sm" />
        <button @click="open = !open" class="rounded-lg border border-slate-200 px-3 py-1 text-sm text-arena-navy">Menú</button>
    </div>

    <nav :class="open ? 'block' : 'hidden'" class="lg:block lg:min-h-screen brand-rail px-4 py-6 text-white">
        <div class="hidden lg:flex flex-col items-center px-2 mb-8">
            <div class="rounded-3xl bg-white p-3 shadow-lg shadow-black/20 w-full">
                <x-brand variant="full" size="lg" class="w-full" />
            </div>
            <p class="mt-3 text-[10px] font-semibold tracking-[0.16em] uppercase text-arena-lime text-center">
                Torneos · Eventos · Pasión
            </p>
        </div>

        <div class="space-y-1">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="{{ request()->routeIs($link['match'])
                        ? 'block rounded-xl px-3 py-2.5 text-sm font-semibold bg-arena-lime text-arena-navy shadow-sm'
                        : 'block rounded-xl px-3 py-2.5 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <div class="mt-10 px-3 text-xs uppercase tracking-wider text-white/40">Cuenta</div>
        <div class="mt-2 space-y-1">
            @if (! $user->isPlayer())
                <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white">Perfil</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left block rounded-xl px-3 py-2.5 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white">Salir</button>
            </form>
        </div>

        <div class="mt-10 mx-1 rounded-2xl border border-white/10 bg-white/5 p-4 text-xs text-white/70">
            <p class="font-semibold text-white">{{ $user->name }}</p>
            <p class="mt-1 break-all">{{ $user->email }}</p>
            <p class="mt-2 uppercase tracking-wider text-arena-lime/80">{{ $user->isMaster() ? 'master' : $user->role }}</p>
        </div>
    </nav>
</aside>
