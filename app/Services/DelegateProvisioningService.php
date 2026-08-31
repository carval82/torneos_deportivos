<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DelegateProvisioningService
{
    public function __construct(private readonly MatchSheetService $sheets) {}

    /**
     * Inscribe el equipo al torneo si todavía no está.
     */
    public function enrollTeam(Tournament $tournament, Team $team): void
    {
        if ($tournament->teams()->where('teams.id', $team->id)->exists()) {
            return;
        }

        if ($tournament->max_teams && $tournament->teams()->count() >= $tournament->max_teams) {
            throw ValidationException::withMessages([
                'team_id' => "Este torneo admite como máximo {$tournament->max_teams} equipos.",
            ]);
        }

        $tournament->teams()->syncWithoutDetaching([$team->id]);
        $this->sheets->enrollTeamRoster($tournament->id, $team->id);

        if ($tournament->status === Tournament::STATUS_DRAFT) {
            $tournament->update(['status' => Tournament::STATUS_INSCRIPTION]);
        }
    }

    /**
     * @param  array{name: string, email: string, document_type: string, document_number: string, is_disciplinary_committee?: bool}  $data
     */
    public function createForTeam(Tournament $tournament, Team $team, array $data): User
    {
        $this->enrollTeam($tournament, $team);

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
                'password' => $document,
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
                'password' => $document,
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
