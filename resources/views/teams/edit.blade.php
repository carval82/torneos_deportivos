<x-app-layout>
    <x-slot name="header">Editar {{ $team->name }}</x-slot>
    <div class="card p-6 max-w-3xl">
        <form method="POST" action="{{ route('teams.update', $team) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('teams._form', ['submit' => 'Guardar'])
        </form>
    </div>
</x-app-layout>
