<x-app-layout>
    <x-slot name="header">Perfil</x-slot>

    <div class="space-y-6 max-w-2xl">
        <div class="card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="card p-6">
            @include('profile.partials.update-password-form')
        </div>
        <div class="card p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
