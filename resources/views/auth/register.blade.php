<x-guest-layout>
    <h1 class="text-2xl font-semibold mb-6">Crear cuenta</h1>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm text-slate-600">Nombre</label>
            <input name="name" value="{{ old('name') }}" class="field" required autofocus>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Correo</label>
            <input name="email" type="email" value="{{ old('email') }}" class="field" required>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Contraseña</label>
            <input name="password" type="password" class="field" required>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Confirmar contraseña</label>
            <input name="password_confirmation" type="password" class="field" required>
        </div>
        <div class="flex items-center justify-between">
            <a class="text-sm text-arena-navy" href="{{ route('login') }}">Ya tengo cuenta</a>
            <button class="btn-primary">Registrarme</button>
        </div>
    </form>
</x-guest-layout>
