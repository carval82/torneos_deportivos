@csrf
<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="text-sm text-slate-600">Nombre</label>
        <input name="name" value="{{ old('name', $team->name ?? '') }}" class="field" required>
    </div>
    <div>
        <label class="text-sm text-slate-600">Sigla</label>
        <input name="short_name" value="{{ old('short_name', $team->short_name ?? '') }}" class="field" maxlength="10">
    </div>
    <div>
        <label class="text-sm text-slate-600">Ciudad</label>
        <input name="city" value="{{ old('city', $team->city ?? '') }}" class="field">
    </div>
    <div>
        <label class="text-sm text-slate-600">DT / entrenador</label>
        <input name="coach" value="{{ old('coach', $team->coach ?? '') }}" class="field">
    </div>
    <div>
        <label class="text-sm text-slate-600">Color</label>
        <input type="color" name="primary_color" value="{{ old('primary_color', $team->primary_color ?? '#10b981') }}" class="field h-12 p-1">
    </div>
    <div class="md:col-span-2">
        <label class="text-sm text-slate-600">Escudo / logo</label>
        <input type="file" name="logo" accept="image/*" class="field">
    </div>
</div>
<div class="mt-6 flex gap-3">
    <button class="btn-primary">{{ $submit ?? 'Guardar' }}</button>
    <a href="{{ isset($team) ? route('teams.show', $team) : route('teams.index') }}" class="btn-ghost">Cancelar</a>
</div>
