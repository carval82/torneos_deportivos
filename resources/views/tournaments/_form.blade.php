@php
    $fieldsText = old('fields_text', isset($tournament) ? implode("\n", $tournament->fieldList()) : "Cancha 1\nCancha 2\nCancha 3\nCancha 4\nCancha 5");
    $selectedDays = old('play_days', $tournament->play_days ?? [0]);
    $selectedDays = array_map('intval', $selectedDays);
@endphp
@csrf

<div class="space-y-8">
    <section>
        <h2 class="text-lg font-semibold mb-1">Datos del torneo</h2>
        <p class="text-sm text-slate-500 mb-4">Vos definís el nombre, el deporte y el cupo de equipos.</p>
        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600">Nombre del torneo</label>
                <input name="name" value="{{ old('name', $tournament->name ?? '') }}" class="field" required>
            </div>
            <div>
                <label class="text-sm text-slate-600">Deporte</label>
                <select name="sport_id" class="field" required>
                    @foreach ($sports as $sport)
                        <option value="{{ $sport->id }}" @selected(old('sport_id', $tournament->sport_id ?? '') == $sport->id)>{{ $sport->icon }} {{ $sport->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Temporada</label>
                <input name="season" value="{{ old('season', $tournament->season ?? date('Y')) }}" class="field">
            </div>
            <div>
                <label class="text-sm text-slate-600">Formato</label>
                <select name="format" class="field">
                    <option value="league" @selected(old('format', $tournament->format ?? 'league') === 'league')>Liga (todos contra todos)</option>
                    <option value="knockout" @selected(old('format', $tournament->format ?? '') === 'knockout')>Eliminación directa</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Máximo de equipos</label>
                <input type="number" name="max_teams" min="2" max="128" value="{{ old('max_teams', $tournament->max_teams ?? 20) }}" class="field">
                <p class="text-xs text-slate-500 mt-1">Ejemplo: 20 equipos → 10 partidos por fecha.</p>
            </div>
            <div class="flex items-center gap-3 pt-6">
                <input type="hidden" name="double_round" value="0">
                <input type="checkbox" name="double_round" value="1" class="rounded border-slate-300 bg-white text-arena-limeDark" @checked(old('double_round', $tournament->double_round ?? false))>
                <label class="text-sm text-slate-600">Ida y vuelta</label>
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-semibold mb-1">Edad y género (tus reglas)</h2>
        <p class="text-sm text-slate-500 mb-4">No hay categorías fijas: poné el tope que quieras y cómo se llama.</p>
        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600">Nombre de la categoría</label>
                <input name="category_label" value="{{ old('category_label', $tournament->category_label ?? '') }}" class="field" placeholder="Ej: Sub-17 complejo, Libre +35, Femenino A">
            </div>
            <div>
                <label class="text-sm text-slate-600">Edad mínima</label>
                <input type="number" name="min_age" min="5" max="80" value="{{ old('min_age', $tournament->min_age ?? '') }}" class="field" placeholder="Opcional">
            </div>
            <div>
                <label class="text-sm text-slate-600">Edad máxima</label>
                <input type="number" name="max_age" min="5" max="80" value="{{ old('max_age', $tournament->max_age ?? '') }}" class="field" placeholder="Opcional">
            </div>
            <div>
                <label class="text-sm text-slate-600">Género</label>
                <select name="gender_rule" class="field">
                    @foreach (['mixto' => 'Mixto', 'masculino' => 'Masculino', 'femenino' => 'Femenino'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender_rule', $tournament->gender_rule ?? 'mixto') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-semibold mb-1">Complejo, canchas y días de juego</h2>
        <p class="text-sm text-slate-500 mb-4">Si jugás solo los domingos con 5 canchas, el fixture reparte los partidos entre esas canchas y horarios.</p>
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="text-sm text-slate-600">Nombre del complejo</label>
                <input name="complex_name" value="{{ old('complex_name', $tournament->complex_name ?? '') }}" class="field" placeholder="Complejo Arena">
            </div>
            <div>
                <label class="text-sm text-slate-600">Sede / dirección</label>
                <input name="venue" value="{{ old('venue', $tournament->venue ?? '') }}" class="field">
            </div>
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600">Canchas (una por línea)</label>
                <textarea name="fields_text" rows="5" class="field" placeholder="Cancha 1&#10;Cancha 2">{{ $fieldsText }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600 mb-2 block">Días de juego</label>
                <div class="flex flex-wrap gap-3">
                    @foreach ($weekdays as $value => $label)
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <input type="checkbox" name="play_days[]" value="{{ $value }}" class="rounded border-slate-300 bg-white text-arena-limeDark" @checked(in_array($value, $selectedDays, true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="text-sm text-slate-600">Hora de inicio</label>
                <input type="time" name="match_start_time" value="{{ old('match_start_time', isset($tournament) && $tournament->match_start_time ? \Illuminate\Support\Str::of($tournament->match_start_time)->substr(0,5) : '09:00') }}" class="field">
            </div>
            <div>
                <label class="text-sm text-slate-600">Minutos entre turnos</label>
                <input type="number" name="match_interval_minutes" min="30" max="240" value="{{ old('match_interval_minutes', $tournament->match_interval_minutes ?? 90) }}" class="field">
                <p class="text-xs text-slate-500 mt-1">Si hay más partidos que canchas, se abren turnos (09:00, 10:30…).</p>
            </div>
            <div>
                <label class="text-sm text-slate-600">Días entre fechas</label>
                <input type="number" name="days_between_rounds" min="1" max="30" value="{{ old('days_between_rounds', $tournament->days_between_rounds ?? 7) }}" class="field">
                <p class="text-xs text-slate-500 mt-1">Para jugar todos los domingos usá 7. Si llueve, la fecha se pasa al próximo domingo y se corren las siguientes.</p>
            </div>
            <div>
                <label class="text-sm text-slate-600">Tipo de cancha</label>
                <select name="field_surface" class="field">
                    <option value="natural" @selected(old('field_surface', $tournament->field_surface ?? 'natural') === 'natural')>Césped natural</option>
                    <option value="artificial" @selected(old('field_surface', $tournament->field_surface ?? '') === 'artificial')>Césped sintético</option>
                    <option value="mixed" @selected(old('field_surface', $tournament->field_surface ?? '') === 'mixed')>Mixta</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Inicio del torneo</label>
                <input type="date" name="start_date" value="{{ old('start_date', isset($tournament) ? $tournament->start_date?->format('Y-m-d') : '') }}" class="field">
            </div>
            <div>
                <label class="text-sm text-slate-600">Fin estimado</label>
                <input type="date" name="end_date" value="{{ old('end_date', isset($tournament) ? $tournament->end_date?->format('Y-m-d') : '') }}" class="field">
            </div>
            <div>
                <label class="text-sm text-slate-600">Fechas de sanción por roja</label>
                <input type="number" name="red_ban_matches" min="1" max="10" value="{{ old('red_ban_matches', $tournament->red_ban_matches ?? 1) }}" class="field">
            </div>
            <div>
                <label class="text-sm text-slate-600">Fechas por doble amarilla</label>
                <input type="number" name="double_yellow_ban_matches" min="1" max="10" value="{{ old('double_yellow_ban_matches', $tournament->double_yellow_ban_matches ?? 1) }}" class="field">
                <p class="text-xs text-slate-500 mt-1">2 amarillas en el mismo partido = expulsión + esta sanción.</p>
            </div>
        </div>
    </section>

    @php
        $cr = old('competition_rules', isset($tournament) ? ($tournament->competition_rules ?? $competitionRulesDefaults) : $competitionRulesDefaults);
        if (! is_array($cr)) {
            $cr = $competitionRulesDefaults;
        }
        $cr = array_merge($competitionRulesDefaults, $cr);
    @endphp
    <section>
        <h2 class="text-lg font-semibold mb-1">W.O. y descalificación</h2>
        <p class="text-sm text-slate-500 mb-4">Reglas ejecutables: el sistema las aplica solo cuando un equipo no se presenta.</p>
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="text-sm text-slate-600">Goles a favor por W.O.</label>
                <input type="number" name="walkover_goals_for" min="0" max="20" value="{{ old('walkover_goals_for', $cr['walkover_goals_for']) }}" class="field">
            </div>
            <div>
                <label class="text-sm text-slate-600">Goles en contra por W.O.</label>
                <input type="number" name="walkover_goals_against" min="0" max="20" value="{{ old('walkover_goals_against', $cr['walkover_goals_against']) }}" class="field">
            </div>
            <div>
                <label class="text-sm text-slate-600">Inasistencias para descalificar</label>
                <input type="number" name="max_no_shows_before_dq" min="1" max="20" value="{{ old('max_no_shows_before_dq', $cr['max_no_shows_before_dq']) }}" class="field">
                <p class="text-xs text-slate-500 mt-1">Al llegar a este número el equipo queda fuera del torneo.</p>
            </div>
            <div>
                <label class="text-sm text-slate-600">Al descalificar</label>
                <select name="on_disqualification" class="field">
                    <option value="wo_remaining" @selected(old('on_disqualification', $cr['on_disqualification']) === 'wo_remaining')>Partidos pendientes = W.O. al rival</option>
                    <option value="bye_rest" @selected(old('on_disqualification', $cr['on_disqualification']) === 'bye_rest')>Rivales descansan (bye)</option>
                </select>
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-semibold mb-1">Reglamento para los jugadores</h2>
        <p class="text-sm text-slate-500 mb-4">Esto se publica para que nadie diga que no conocía las normas.</p>
        <div class="grid gap-5">
            <div>
                <label class="text-sm text-slate-600">Resumen corto</label>
                <input name="rules_summary" value="{{ old('rules_summary', $tournament->rules_summary ?? '') }}" class="field" placeholder="Solo domingos, Sub-17, DNI obligatorio, 5 canchas del complejo">
            </div>
            <div>
                <label class="text-sm text-slate-600">Reglamento completo</label>
                <textarea name="rules" rows="8" class="field" placeholder="Edades, documentación, sanciones, aplazos, puntualidad, etc.">{{ old('rules', $tournament->rules ?? '') }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="hidden" name="rules_published" value="0">
                <input type="checkbox" name="rules_published" value="1" class="rounded border-slate-300 bg-white text-arena-limeDark" @checked(old('rules_published', $tournament->rules_published ?? true))>
                Publicar reglamento para jugadores y equipos
            </label>
            <p class="mt-3 text-xs text-slate-500">
                La app es privada: solo organizador, delegado, jugador y master pueden entrar.
            </p>
        </div>
    </section>
</div>

<div class="mt-8 flex gap-3">
    <button class="btn-primary">{{ $submit ?? 'Guardar' }}</button>
    <a href="{{ isset($tournament) ? route('tournaments.show', $tournament) : route('tournaments.index') }}" class="btn-ghost">Cancelar</a>
</div>
