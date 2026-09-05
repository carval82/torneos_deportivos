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
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm text-slate-600">Tipo doc.</label>
                <select name="document_type" class="field">
                    @foreach (['Cédula', 'DNI', 'Pasaporte'] as $type)
                        <option value="{{ $type }}" @selected(old('document_type', 'Cédula') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600">Cédula / documento</label>
                <input name="document_number" value="{{ old('document_number') }}" class="field" required placeholder="Solo números">
                <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
            </div>
        </div>
        <p class="text-xs text-slate-500">Cada torneo cuesta {{ \App\Models\TournamentPayment::feeLabel() }}. La cédula es única: no sirve abrir otra cuenta con el mismo documento.</p>
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
