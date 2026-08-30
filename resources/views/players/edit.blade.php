<x-app-layout>
    <x-slot name="header">Editar ficha</x-slot>
    <div class="card p-6 max-w-4xl">
        <form method="POST" action="{{ route('players.update', $player) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('players._form', ['submit' => 'Guardar ficha'])
        </form>
    </div>
</x-app-layout>
