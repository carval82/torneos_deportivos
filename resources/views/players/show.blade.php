<x-app-layout>
    <x-slot name="header">{{ $player->displayName() }}</x-slot>
    <x-slot name="subheader">{{ $player->team?->name ?? 'Sin equipo' }} · {{ $player->position ?: 'Sin posición' }}</x-slot>

    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('players.edit', $player) }}" class="btn-primary">Editar ficha</a>
        @if ($player->team)
            <a href="{{ route('teams.show', $player->team) }}" class="btn-ghost">Ver equipo</a>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card p-6">
            <div class="aspect-square rounded-3xl overflow-hidden bg-slate-50 mb-4">
                @if ($player->photoUrl())
                    <img src="{{ $player->photoUrl() }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="h-full grid place-items-center text-slate-500">Sin foto</div>
                @endif
            </div>
            <p class="text-sm text-slate-500">Documento</p>
            <p class="font-medium">{{ $player->document_type }} {{ $player->document_number }}</p>
            @if ($player->documentPhotoUrl())
                <img src="{{ $player->documentPhotoUrl() }}" alt="Documento" class="mt-4 rounded-2xl border border-slate-200">
            @else
                <p class="mt-3 text-amber-700 text-sm">Falta la foto del documento.</p>
            @endif
        </section>

        <section class="card p-6 lg:col-span-2">
            <h2 class="font-semibold mb-4">Habilitación</h2>
            <div class="rounded-2xl border px-4 py-3 mb-6 {{ $eligibility['eligible'] ? 'border-arena-lime/40 bg-arena-mist text-arena-navy' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                <p class="font-medium">{{ $eligibility['eligible'] ? 'Habilitado' : 'No habilitado' }}</p>
                <p class="text-sm mt-1">Edad: {{ $eligibility['age'] ?? 'sin dato' }} {{ $tournament ? 'al inicio de '.$tournament->name : '' }}</p>
                @if ($eligibility['reason'])
                    <p class="text-sm mt-1">{{ $eligibility['reason'] }}</p>
                @endif
                @foreach ($eligibility['warnings'] as $warning)
                    <p class="text-sm mt-1">{{ $warning }}</p>
                @endforeach
            </div>

            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Nacimiento</dt><dd>{{ $player->birthdate?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Dorsal</dt><dd>{{ $player->jersey_number ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Goles / puntos</dt><dd>{{ $goals }}</dd></div>
                <div><dt class="text-slate-500">Nacionalidad</dt><dd>{{ $player->nationality }}</dd></div>
            </dl>
        </section>
    </div>
</x-app-layout>
