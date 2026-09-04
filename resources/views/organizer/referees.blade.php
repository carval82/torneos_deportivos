<x-app-layout>
    <x-slot name="header">Árbitros</x-slot>
    <x-slot name="subheader">Creá árbitros y coordinadores. La contraseña inicial es el número de documento, igual que los delegados.</x-slot>

    <div class="grid gap-6 lg:grid-cols-5">
        <section class="card p-6 lg:col-span-2 h-fit">
            <h2 class="font-semibold text-lg">Nuevo oficial</h2>
            <p class="text-sm text-slate-500 mt-1 mb-5">
                Después el coordinador arbitral los asigna a cada partido del fixture.
            </p>

            <form method="POST" action="{{ route('organizer.referees.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm text-slate-600">Nombre completo</label>
                    <input name="name" value="{{ old('name') }}" class="field" required placeholder="Carlos Gómez">
                    @error('name') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm text-slate-600">Correo</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="field" required placeholder="arbitro@club.com">
                    @error('email') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
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
                        <input name="document_number" value="{{ old('document_number') }}" class="field" required placeholder="12345678">
                        @error('document_number') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="text-sm text-slate-600">Cargo</label>
                    <select name="role" class="field">
                        <option value="referee" @selected(old('role', 'referee') === 'referee')>Árbitro</option>
                        @if ($canCreateCoordinator)
                            <option value="referee_coordinator" @selected(old('role') === 'referee_coordinator')>Coordinador arbitral</option>
                        @endif
                    </select>
                </div>
                <button class="btn-primary w-full">Crear oficial</button>
            </form>
        </section>

        <section class="card p-6 lg:col-span-3">
            <h2 class="font-semibold text-lg mb-4">Cuerpo arbitral</h2>
            @forelse ($officials as $official)
                <div class="rounded-2xl border border-slate-100 px-4 py-3 mb-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-arena-navy">{{ $official->name }}</p>
                            <p class="text-sm text-slate-500">{{ $official->email }}</p>
                            <p class="text-sm text-slate-500">
                                {{ $official->document_type }} {{ $official->document_number }}
                                · clave = documento
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-arena-mist px-3 py-1 text-xs font-semibold text-arena-navy">
                            {{ $official->roleLabel() }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">{{ $official->officiated_games_count }} partido(s) asignados</p>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-slate-500">
                    Todavía no hay árbitros. Creá el primero con el formulario.
                </div>
            @endforelse
        </section>
    </div>
</x-app-layout>
