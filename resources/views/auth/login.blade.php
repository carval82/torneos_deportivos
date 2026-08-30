<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <h1 class="text-2xl font-semibold mb-1">Entrar</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm text-slate-600">Correo</label>
            <input id="email" class="field" type="email" name="email" value="{{ old('email', 'pcapacho24@gmail.com') }}" required autofocus>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Contraseña</label>
            <input id="password" class="field" type="password" name="password" required>
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
</x-guest-layout>
