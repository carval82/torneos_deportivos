<x-app-layout>
    <x-slot name="header">Panel delegado</x-slot>
    <x-slot name="subheader">Gestioná las plantillas de tus equipos.</x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($teams as $team)
            <a href="{{ route('delegate.roster', $team) }}" class="card p-6 hover:border-arena-lime transition">
                <p class="font-semibold text-lg">{{ $team->name }}</p>
                <p class="text-sm text-slate-500 mt-1">{{ $team->city ?: 'Sin ciudad' }} · {{ $team->players()->count() }} jugadores</p>
                <span class="inline-block mt-4 text-sm font-semibold text-arena-limeDark">Abrir plantilla</span>
            </a>
        @empty
            <div class="card p-8 text-slate-500 sm:col-span-2 lg:col-span-3">
                Todavía no tenés equipos asignados. Pedile al organizador el link de invitación.
            </div>
        @endforelse
    </div>
</x-app-layout>
