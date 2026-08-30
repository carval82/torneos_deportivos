<x-app-layout>
    <x-slot name="header">Nueva ficha de jugador</x-slot>
    <div class="card p-6 max-w-4xl">
        <form method="POST" action="{{ route('players.store') }}" enctype="multipart/form-data">
            @include('players._form', ['submit' => 'Cargar jugador'])
        </form>
    </div>
</x-app-layout>
