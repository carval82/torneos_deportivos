<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@isset($header){{ trim(strip_tags($header)) }} · @endisset{{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/brand/mark-192.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|outfit:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-app text-arena-navy">
        <div class="min-h-screen lg:flex">
            @include('layouts.navigation')

            <div class="flex-1 min-w-0">
                <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div class="px-4 sm:px-8 py-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            @isset($header)
                                <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-arena-navy truncate">{{ $header }}</h1>
                            @endisset
                            @isset($subheader)
                                <p class="text-sm text-slate-500 mt-1">{{ $subheader }}</p>
                            @endisset
                        </div>
                        <div class="hidden sm:flex items-center gap-2 text-sm text-slate-500">
                            <span class="h-2 w-2 rounded-full bg-arena-lime"></span>
                            {{ now()->format('d/m/Y') }}
                        </div>
                    </div>
                </header>

                <main class="p-4 sm:p-8">
                    @if (session('status'))
                        <div class="mb-6 rounded-2xl border border-arena-lime/40 bg-arena-lime/15 px-4 py-3 text-arena-navy">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
                            <ul class="list-disc ms-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
        @include('partials.asistente')
    </body>
</html>
