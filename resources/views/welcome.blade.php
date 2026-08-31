<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Arena Players · Torneos y eventos</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/brand/mark-192.png') }}">
        <link rel="preload" as="image" href="{{ asset('images/brand/fondo-1920.jpg') }}">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|outfit:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-arena-hero text-white font-sans min-h-screen">
        <header class="max-w-6xl mx-auto px-6 py-5 flex items-center justify-between gap-4">
            <a href="/" class="inline-flex items-center gap-3">
                <img src="{{ asset('images/brand/mark-192.png') }}" alt="Arena Players" class="h-12 w-12 rounded-2xl bg-white object-contain p-1 shadow-lg ring-1 ring-white/20">
                <span class="hidden sm:block">
                    <span class="block text-sm font-black tracking-[0.12em] text-white">ARENA PLAYERS</span>
                    <span class="block text-[10px] font-semibold tracking-[0.14em] uppercase text-arena-lime">Torneos · Eventos · Pasión</span>
                </span>
            </a>
            <nav class="flex gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-accent">Ir al panel</a>
                @else
                    <a href="{{ route('login') }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/15">Entrar</a>
                    <a href="{{ route('register') }}" class="btn-accent">Crear cuenta</a>
                @endauth
            </nav>
        </header>

        <main class="max-w-6xl mx-auto px-6 py-10 sm:py-20">
            <div class="max-w-xl glass-panel-dark p-8 sm:p-10">
                <p class="text-arena-lime text-sm font-semibold tracking-[0.16em] uppercase">Fútbol · Vóley · Básquet · y más</p>
                <h1 class="mt-4 text-4xl sm:text-5xl font-semibold leading-tight">
                    Gestioná torneos y eventos con la pasión que nos une.
                </h1>
                <p class="mt-6 text-lg text-white/80">
                    Acceso solo con login: organizador, delegado o jugador. El primer torneo del organizador es gratis; crear o renovar otro cuesta $70.000 COP (aprueba el master).
                </p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-accent">Soy organizador</a>
                    <a href="{{ route('player.login') }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/15">Soy jugador</a>
                    <a href="{{ route('login') }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/15">Entrar</a>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-12">
                @foreach ([
                    ['Planillas', 'Foto del jugador y del DNI para categorías con tope de edad.'],
                    ['Fixture', 'Todos contra todos o eliminación, con carga de goles y W.O.'],
                    ['Estadística', 'Tabla, goleadores, asistencia y curvas por fecha.'],
                    ['Flutter', 'API con Sanctum lista para la app móvil.'],
                ] as $item)
                    <div class="glass-panel-dark p-5">
                        <h2 class="font-semibold text-arena-lime">{{ $item[0] }}</h2>
                        <p class="text-sm text-white/75 mt-2">{{ $item[1] }}</p>
                    </div>
                @endforeach
            </div>
        </main>
    </body>
</html>
