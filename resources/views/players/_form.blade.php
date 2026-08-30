@csrf
<div class="grid gap-5 md:grid-cols-2" x-data="{ photo: null, doc: null }">
    <div>
        <label class="text-sm text-slate-600">Nombre</label>
        <input name="first_name" value="{{ old('first_name', $player->first_name ?? '') }}" class="field" required>
    </div>
    <div>
        <label class="text-sm text-slate-600">Apellido</label>
        <input name="last_name" value="{{ old('last_name', $player->last_name ?? '') }}" class="field" required>
    </div>
    <div>
        <label class="text-sm text-slate-600">Equipo</label>
        <select name="team_id" class="field">
            <option value="">Sin equipo</option>
            @foreach ($teams as $team)
                <option value="{{ $team->id }}" @selected(old('team_id', $player->team_id ?? $teamId ?? '') == $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm text-slate-600">Posición</label>
        <input name="position" value="{{ old('position', $player->position ?? '') }}" class="field" placeholder="Delantero, armador, etc.">
    </div>
    <div>
        <label class="text-sm text-slate-600">Tipo de documento</label>
        <select name="document_type" class="field">
            @foreach (['DNI','Cédula','Pasaporte'] as $type)
                <option value="{{ $type }}" @selected(old('document_type', $player->document_type ?? 'DNI') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm text-slate-600">Número de documento</label>
        <input name="document_number" value="{{ old('document_number', $player->document_number ?? '') }}" class="field" required>
    </div>
    <div>
        <label class="text-sm text-slate-600">Fecha de nacimiento</label>
        <input type="date" name="birthdate" value="{{ old('birthdate', isset($player) ? $player->birthdate?->format('Y-m-d') : '') }}" class="field">
        <p class="text-xs text-slate-500 mt-1">Se usa para validar Sub-13, Sub-15, Sub-17 y el resto de tope de edad.</p>
    </div>
    <div>
        <label class="text-sm text-slate-600">Género</label>
        <select name="gender" class="field">
            <option value="masculino" @selected(old('gender', $player->gender ?? '') === 'masculino')>Masculino</option>
            <option value="femenino" @selected(old('gender', $player->gender ?? '') === 'femenino')>Femenino</option>
            <option value="mixto" @selected(old('gender', $player->gender ?? '') === 'mixto')>Mixto</option>
        </select>
    </div>
    <div>
        <label class="text-sm text-slate-600">Dorsal</label>
        <input type="number" name="jersey_number" value="{{ old('jersey_number', $player->jersey_number ?? '') }}" class="field" min="1" max="99">
    </div>
    <div>
        <label class="text-sm text-slate-600">Nacionalidad</label>
        <input name="nationality" value="{{ old('nationality', $player->nationality ?? 'Argentina') }}" class="field">
    </div>
    <div>
        <label class="text-sm text-slate-600">Foto de ficha</label>
        <input type="file" name="photo" accept="image/*" capture="user" class="field" @change="photo = URL.createObjectURL($event.target.files[0])">
        <p class="text-xs text-slate-500 mt-1">En el celular abre la cámara para la foto carnet.</p>
        <template x-if="photo"><img :src="photo" class="mt-3 h-28 w-28 rounded-2xl object-cover"></template>
    </div>
    <div>
        <label class="text-sm text-slate-600">Foto del documento</label>
        <input type="file" name="document_photo" accept="image/*" capture="environment" class="field" @change="doc = URL.createObjectURL($event.target.files[0])">
        <p class="text-xs text-slate-500 mt-1">Sacá el DNI o cédula para acreditar edad.</p>
        <template x-if="doc"><img :src="doc" class="mt-3 h-28 w-44 rounded-2xl object-cover"></template>
    </div>
</div>
<div class="mt-6 flex gap-3">
    <button class="btn-primary">{{ $submit ?? 'Guardar ficha' }}</button>
    <a href="{{ isset($player) ? route('players.show', $player) : route('players.index') }}" class="btn-ghost">Cancelar</a>
</div>
