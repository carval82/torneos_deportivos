<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name'))</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|outfit:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-app text-arena-navy font-sans min-h-screen">
        <header class="border-b border-slate-200 bg-white/90 backdrop-blur sticky top-0 z-20">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
                <a href="{{ route('welcome') }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset('images/brand/mark-192.png') }}" alt="Arena Players" class="h-10 w-10 rounded-xl bg-white object-contain p-1 ring-1 ring-arena-navy/10">
                    <span class="hidden sm:block">
                        <span class="block text-sm font-black tracking-[0.12em]">ARENA PLAYERS</span>
                        <span class="block text-[10px] font-semibold tracking-[0.14em] uppercase text-arena-limeDark">Torneos · Eventos · Pasión</span>
                    </span>
                </a>
                <div class="flex gap-2">
                    <a href="{{ route('app.download') }}" class="btn-ghost text-sm">Descargar app</a>
                    <a href="{{ route('player.login') }}" class="btn-ghost text-sm">Soy jugador</a>
                    <a href="{{ route('referee.login') }}" class="btn-ghost text-sm">Soy árbitro</a>
                    <a href="{{ route('login') }}" class="btn-primary text-sm">Organizador</a>
                </div>
            </div>
        </header>
        <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-arena-lime/40 bg-arena-lime/15 px-4 py-3 text-arena-navy">
                    {{ session('status') }}
                </div>
            @endif
            @yield('content')
        </main>
        @include('partials.asistente')
    </body>
</html>
