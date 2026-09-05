@extends('layouts.public')

@section('title', 'Entrar como jugador · Arena Players')

@section('content')
    <div class="max-w-md mx-auto">
        <div class="card p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-arena-limeDark">Acceso jugador</p>
            <h1 class="mt-2 text-2xl font-semibold">Consultá con tu cédula</h1>
            <p class="mt-2 text-sm text-slate-600">
                Verificamos si tu documento está en una plantilla, a qué torneo estás vinculado y te mostramos fechas, resultados y tabla de tu equipo.
            </p>

            <form method="POST" action="{{ route('player.login.store') }}" class="mt-8 space-y-4">
                @csrf
                <div>
                    <label class="text-sm text-slate-600">Cédula</label>
                    <input name="document_number" value="{{ old('document_number') }}" class="field" required inputmode="numeric" autocomplete="off" placeholder="Número de cédula" autofocus>
                    @error('document_number') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <button class="btn-accent w-full">Ver mi información</button>
            </form>
            <p class="mt-6 text-sm text-slate-500">
                ¿Tenés Android?
                <a href="{{ route('app.download') }}" class="font-semibold text-arena-navy">Descargá la app</a>
            </p>
        </div>
    </div>
@endsection
