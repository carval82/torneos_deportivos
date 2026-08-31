<x-app-layout>
    <x-slot name="header">Plantilla · {{ $team->name }}</x-slot>
    <x-slot name="subheader">
        @if ($tournament)
            Torneo: {{ $tournament->name }}
        @else
            Elegí un torneo para sincronizar la planilla.
        @endif
    </x-slot>

    <div class="mb-6 flex flex-wrap gap-3 items-center">
        <a href="{{ route('delegate.index') }}" class="btn-ghost">Volver</a>
        @if ($tournaments->isNotEmpty())
            <form method="GET" class="flex gap-2 items-center">
                <select name="tournament" class="field mt-0" onchange="this.form.submit()">
                    @foreach ($tournaments as $item)
                        <option value="{{ $item->id }}" @selected(($tournament?->id) === $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if ($rosterStatus ?? null)
        <div class="mb-6 rounded-2xl border px-4 py-3 text-sm {{ ($rosterStatus['open'] ?? false) ? 'border-arena-lime/40 bg-arena-mist text-arena-navy' : 'border-rose-200 bg-rose-50 text-rose-800' }}">
            {{ $rosterStatus['message'] }}
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="card p-6 lg:col-span-2">
            <h2 class="font-semibold mb-4">Jugadores ({{ $team->players->count() }})</h2>
            <div class="space-y-3">
                @forelse ($team->players as $player)
                    <div class="rounded-2xl border border-slate-100 px-4 py-3">
                        <div class="flex flex-wrap justify-between gap-2">
                            <div>
                                <p class="font-medium">{{ $player->displayName() }}
                                    @if ($player->jersey_number)
                                        <span class="text-slate-400">#{{ $player->jersey_number }}</span>
                                    @endif
                                </p>
                                <p class="text-sm text-slate-500">
                                    {{ $player->document_type }} {{ $player->document_number }}
                                    · {{ $player->phone ?: 'Sin celular' }}
                                    · {{ $player->position ?: 'Sin posición' }}
                                </p>
                                @if ($tournament && isset($eligibility[$player->id]))
                                    @if (! ($eligibility[$player->id]['eligible'] ?? true))
                                        <p class="text-xs text-rose-700 mt-1">{{ $eligibility[$player->id]['reason'] ?? 'No elegible' }}</p>
                                    @elseif (! empty($eligibility[$player->id]['warnings']))
                                        <p class="text-xs text-amber-700 mt-1">{{ implode(' · ', $eligibility[$player->id]['warnings']) }}</p>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <details class="mt-3">
                            <summary class="text-sm font-semibold text-arena-limeDark cursor-pointer">Editar</summary>
                            <form method="POST" action="{{ route('delegate.players.update', [$team, $player]) }}" enctype="multipart/form-data" class="mt-3 grid sm:grid-cols-2 gap-3">
                                @csrf
                                @method('PUT')
                                @if ($tournament)
                                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                                @endif
                                <div>
                                    <label class="text-xs text-slate-500">Nombre</label>
                                    <input name="first_name" value="{{ $player->first_name }}" class="field" required>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Apellido</label>
                                    <input name="last_name" value="{{ $player->last_name }}" class="field" required>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Documento</label>
                                    <select name="document_type" class="field">
                                        @foreach (['DNI','Pasaporte','Cédula'] as $type)
                                            <option value="{{ $type }}" @selected($player->document_type === $type)>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Número</label>
                                    <input name="document_number" value="{{ $player->document_number }}" class="field" required>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Nacimiento</label>
                                    <input type="date" name="birthdate" value="{{ $player->birthdate?->format('Y-m-d') }}" class="field" required>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Género</label>
                                    <select name="gender" class="field">
                                        @foreach (['masculino','femenino','mixto'] as $g)
                                            <option value="{{ $g }}" @selected($player->gender === $g)>{{ ucfirst($g) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Celular (opcional)</label>
                                    <input name="phone" value="{{ $player->phone }}" class="field">
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">N° camiseta</label>
                                    <input type="number" name="jersey_number" value="{{ $player->jersey_number }}" class="field" min="0" max="99">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-xs text-slate-500">Posición</label>
                                    <input name="position" value="{{ $player->position }}" class="field">
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Foto del jugador</label>
                                    <input type="file" name="photo" accept="image/*" capture="environment" class="field">
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Foto del documento</label>
                                    <input type="file" name="document_photo" accept="image/*" capture="environment" class="field">
                                </div>
                                <div class="sm:col-span-2 flex flex-wrap gap-2">
                                    <button class="btn-primary">Guardar cambios</button>
                                    @if ($tournament && ! ($eligibility[$player->id]['eligible'] ?? true))
                                        <button
                                            formaction="{{ route('exceptions.store', $tournament) }}"
                                            formmethod="POST"
                                            name="reason"
                                            value="Jugador menor a la categoría. Solicito autorización del master."
                                            class="btn-ghost text-amber-800"
                                        >Pedir excepción de edad</button>
                                        <input type="hidden" name="player_id" value="{{ $player->id }}">
                                        <input type="hidden" name="team_id" value="{{ $team->id }}">
                                    @endif
                                </div>
                            </form>
                        </details>
                    </div>
                @empty
                    <p class="text-slate-500">Todavía no hay jugadores. Cargá el primero a la derecha.</p>
                @endforelse
            </div>
        </section>

        <section class="card p-6 h-fit">
            <h2 class="font-semibold mb-4">Agregar jugador</h2>
            <form method="POST" action="{{ route('delegate.players.store', $team) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @if ($tournament)
                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                @endif
                <div>
                    <label class="text-sm text-slate-600">Nombre</label>
                    <input name="first_name" value="{{ old('first_name') }}" class="field" required>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Apellido</label>
                    <input name="last_name" value="{{ old('last_name') }}" class="field" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-slate-600">Tipo</label>
                        <select name="document_type" class="field">
                            @foreach (['DNI','Pasaporte','Cédula'] as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Documento</label>
                        <input name="document_number" value="{{ old('document_number') }}" class="field" required>
                    </div>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Nacimiento</label>
                    <input type="date" name="birthdate" value="{{ old('birthdate') }}" class="field" required>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Género</label>
                    <select name="gender" class="field">
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="mixto">Mixto</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Celular (opcional)</label>
                    <input name="phone" value="{{ old('phone') }}" class="field" placeholder="3001234567">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-slate-600">Camiseta</label>
                        <input type="number" name="jersey_number" value="{{ old('jersey_number') }}" class="field" min="0" max="99">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Posición</label>
                        <input name="position" value="{{ old('position') }}" class="field">
                    </div>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Foto del jugador</label>
                    <input type="file" name="photo" accept="image/*" capture="user" class="field">
                </div>
                <div>
                    <label class="text-sm text-slate-600">Foto del documento</label>
                    <input type="file" name="document_photo" accept="image/*" capture="environment" class="field">
                </div>
                @error('document_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                @error('roster') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                <button class="btn-accent w-full" @disabled(($rosterStatus['open'] ?? true) === false)>Agregar a la plantilla</button>
            </form>
        </section>
    </div>
</x-app-layout>
