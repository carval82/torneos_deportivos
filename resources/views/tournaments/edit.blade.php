<x-app-layout>
    <x-slot name="header">Editar {{ $tournament->name }}</x-slot>
    <x-slot name="subheader">Podés cambiar reglas, edad, días de juego y canchas en cualquier momento.</x-slot>
    <div class="card p-6 max-w-5xl">
        <form method="POST" action="{{ route('tournaments.update', $tournament) }}">
            @method('PUT')
            @include('tournaments._form', ['submit' => 'Guardar cambios'])
        </form>
    </div>
</x-app-layout>
