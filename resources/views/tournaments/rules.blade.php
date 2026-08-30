<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reglamento · {{ $tournament->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-app text-slate-900 font-sans min-h-screen">
    <main class="max-w-3xl mx-auto px-6 py-10">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
            <img src="{{ asset('images/brand/mark-192.png') }}" alt="Arena Players" class="h-12 w-12 rounded-2xl bg-white object-contain p-1 shadow-sm ring-1 ring-arena-navy/10">
            <span>
                <span class="block text-sm font-black tracking-[0.12em] text-arena-navy">ARENA PLAYERS</span>
                <span class="block text-[10px] font-semibold tracking-[0.14em] uppercase text-arena-limeDark">Torneos · Eventos · Pasión</span>
            </span>
        </a>
        <p class="mt-6 text-xs uppercase tracking-[0.16em] font-semibold text-arena-limeDark">Reglamento oficial</p>
        <h1 class="text-3xl font-semibold mt-2 text-slate-900">{{ $tournament->name }}</h1>
        <p class="text-slate-500 mt-2">{{ $tournament->sport->name }} · {{ $tournament->ageLabel() }} · {{ $tournament->genderLabel() }}</p>

        @if ($tournament->rules_summary)
            <div class="card p-5 mt-6 border-arena-lime/40 bg-arena-mist">
                <p class="font-medium text-arena-navy">{{ $tournament->rules_summary }}</p>
            </div>
        @endif

        <div class="card p-6 mt-6 space-y-3 text-sm text-slate-600">
            <div class="flex justify-between gap-4"><span>Días de juego</span><span class="font-medium text-slate-900">{{ $tournament->playDaysLabel() }}</span></div>
            <div class="flex justify-between gap-4"><span>Complejo</span><span class="font-medium text-slate-900">{{ $tournament->complex_name ?: ($tournament->venue ?: '—') }}</span></div>
            <div class="flex justify-between gap-4"><span>Canchas</span><span class="font-medium text-slate-900">{{ implode(', ', $tournament->fieldList()) }}</span></div>
            <div class="flex justify-between gap-4"><span>Cupo</span><span class="font-medium text-slate-900">{{ $tournament->max_teams ? $tournament->max_teams.' equipos' : 'Sin tope' }}</span></div>
            <div class="flex justify-between gap-4"><span>Edad</span><span class="font-medium text-slate-900">
                @if ($tournament->effectiveMinAge() || $tournament->effectiveMaxAge())
                    {{ $tournament->effectiveMinAge() ?? '—' }} a {{ $tournament->effectiveMaxAge() ?? '—' }} años
                @else
                    Libre
                @endif
            </span></div>
            <div class="flex justify-between gap-4"><span>W.O.</span><span class="font-medium text-slate-900">{{ $competitionRules['walkover_goals_for'] }}-{{ $competitionRules['walkover_goals_against'] }}</span></div>
            <div class="flex justify-between gap-4"><span>Descalificación</span><span class="font-medium text-slate-900">{{ $competitionRules['max_no_shows_before_dq'] }} inasistencia(s)</span></div>
        </div>

        <div class="card p-5 mt-6 border-amber-200 bg-amber-50">
            <h2 class="text-sm font-semibold text-amber-900 mb-1">Competencia automática</h2>
            <p class="text-sm text-amber-900">{{ $competitionNarrative }}</p>
        </div>

        <article class="card p-6 mt-6">
            <h2 class="text-lg font-semibold mb-3 text-slate-900">Normas del torneo</h2>
            <div class="whitespace-pre-wrap text-slate-600 text-sm leading-relaxed">{{ $tournament->rules ?: 'El organizador todavía no cargó el reglamento completo.' }}</div>
        </article>

        <p class="text-xs text-slate-400 mt-8">Al participar, el equipo y sus jugadores declaran conocer y aceptar estas reglas.</p>
    </main>
</body>
</html>
