<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/brand/mark-192.png') }}">
        <link rel="preload" as="image" href="{{ asset('images/brand/fondo-1920.jpg') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|outfit:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-arena-navy antialiased bg-arena-hero">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-10">
            <a href="/" class="mb-8 block">
                <div class="rounded-3xl bg-white/95 p-4 shadow-xl ring-1 ring-white/30 backdrop-blur">
                    <img src="{{ asset('images/brand/logo-320.png') }}" alt="Arena Players" class="h-28 w-auto mx-auto object-contain">
                </div>
                <p class="mt-3 text-center text-[10px] font-semibold tracking-[0.16em] uppercase text-arena-lime">
                    Torneos · Eventos · Pasión que nos une
                </p>
            </a>
            <div class="w-full sm:max-w-md glass-panel p-8">
                {{ $slot }}
            </div>
        </div>
        @include('partials.asistente')
    </body>
</html>
