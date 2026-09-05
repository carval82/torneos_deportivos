@php
    $perfil = request('perfil', 'entrar');
    $copy = match ($perfil) {
        'arbitro' => [
            'kicker' => 'Cuerpo arbitral',
            'title' => 'Soy árbitro',
            'text' => 'Entrá con el correo que te cargó el organizador. La contraseña inicial es tu número de documento. Vas a ver tus partidos y podés cargar el marcador en vivo.',
        ],
        'delegado' => [
            'kicker' => 'Delegado',
            'title' => 'Soy delegado',
            'text' => 'Entrá con tu correo. La contraseña inicial es tu número de documento.',
        ],
        default => [
            'kicker' => 'Organizador · master · árbitro',
            'title' => 'Entrar',
            'text' => 'Usá el correo de tu cuenta. Si sos árbitro o delegado, la clave inicial es el documento.',
        ],
    };
@endphp
<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-arena-limeDark">{{ $copy['kicker'] }}</p>
    <h1 class="text-2xl font-semibold mb-1 mt-1">{{ $copy['title'] }}</h1>
    <p class="text-sm text-slate-600 mb-6">{{ $copy['text'] }}</p>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm text-slate-600">Correo</label>
            <input id="email" class="field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Contraseña</label>
            <input id="password" class="field" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-500">
            <input type="checkbox" name="remember" class="rounded border-slate-300 bg-white text-arena-limeDark">
            Recordarme
        </label>
        <div class="flex items-center justify-between">
            <a class="text-sm text-arena-navy" href="{{ route('password.request') }}">¿Olvidaste la clave?</a>
            <button class="btn-primary">Ingresar</button>
        </div>
    </form>
    <p class="mt-6 text-sm text-slate-500">
        ¿Tenés Android?
        <a href="{{ route('app.download') }}" class="font-semibold text-arena-navy">Descargá la app</a>
    </p>
</x-guest-layout>
