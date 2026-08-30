@extends('layouts.public')

@section('title', 'Invitación delegado · Arena Players')

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="card p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-arena-limeDark">Invitación de delegado</p>
            <h1 class="mt-2 text-2xl font-semibold">{{ $invite->team->name }}</h1>
            <p class="mt-2 text-slate-600">
                Torneo <strong>{{ $invite->tournament->name }}</strong>
                ({{ $invite->tournament->sport?->name }} · {{ $invite->tournament->ageLabel() }}).
            </p>

            @auth
                <form method="POST" action="{{ route('invites.accept', $invite->token) }}" class="mt-8">
                    @csrf
                    <p class="text-sm text-slate-600 mb-4">Vas a aceptar como <strong>{{ auth()->user()->name }}</strong>.</p>
                    <button class="btn-accent w-full">Aceptar y gestionar plantilla</button>
                </form>
            @else
                <form method="POST" action="{{ route('invites.accept', $invite->token) }}" class="mt-8 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm text-slate-600">Nombre</label>
                        <input name="name" value="{{ old('name') }}" class="field" required>
                        @error('name') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Email</label>
                        <input type="email" name="email" value="{{ old('email', $invite->email) }}" class="field" required>
                        @error('email') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Contraseña</label>
                        <input type="password" name="password" class="field" required>
                        @error('password') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="field" required>
                    </div>
                    <button class="btn-accent w-full">Crear cuenta y aceptar</button>
                </form>
                <p class="mt-4 text-sm text-slate-500 text-center">
                    ¿Ya tenés cuenta?
                    <a href="{{ route('login') }}" class="text-arena-limeDark font-semibold">Entrá</a>
                    y volvé a este link.
                </p>
            @endauth
        </div>
    </div>
@endsection
