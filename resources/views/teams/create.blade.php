<x-app-layout>
    <x-slot name="header">Nuevo equipo</x-slot>
    <div class="card p-6 max-w-3xl">
        <form method="POST" action="{{ route('teams.store') }}" enctype="multipart/form-data">
            @include('teams._form', ['submit' => 'Crear equipo'])
        </form>
    </div>
</x-app-layout>
