<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DelegateProvisioningService
{
    /**
     * @param  array{name: string, email: string, document_type: string, document_number: string, is_disciplinary_committee?: bool}  $data
     */
    public function createForTeam(Tournament $tournament, Team $team, array $data): User
    {
        if (! $tournament->teams()->where('teams.id', $team->id)->exists()) {
            throw ValidationException::withMessages([
                'team_id' => 'El equipo no está inscripto en este torneo.',
            ]);
        }

        $document = trim($data['document_number']);
        if ($document === '') {
            throw ValidationException::withMessages([
                'document_number' => 'El documento es obligatorio (también será la contraseña).',
            ]);
        }

        $user = User::query()->where('email', $data['email'])->first();

        if ($user) {
            $user->update([
                'name' => $data['name'],
                'document_type' => $data['document_type'],
                'document_number' => $document,
                'password' => Hash::make($document),
                'role' => in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ORGANIZER], true)
                    ? $user->role
                    : User::ROLE_DELEGATE,
            ]);
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'document_type' => $data['document_type'],
                'document_number' => $document,
                'password' => Hash::make($document),
                'role' => User::ROLE_DELEGATE,
                'email_verified_at' => now(),
            ]);
        }

        $team->delegates()->syncWithoutDetaching([
            $user->id => [
                'role' => 'delegate',
                'is_disciplinary_committee' => (bool) ($data['is_disciplinary_committee'] ?? false),
            ],
        ]);

        return $user->fresh();
    }
}
