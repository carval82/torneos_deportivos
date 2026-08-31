<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\DelegateProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TournamentDelegateController extends Controller
{
    public function __construct(private readonly DelegateProvisioningService $provisioning) {}

    public function store(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('invite', $tournament);

        $data = $request->validate([
            'team_id' => ['nullable', 'exists:teams,id'],
            'new_team_name' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'document_type' => ['required', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['required', 'string', 'max:40'],
            'is_disciplinary_committee' => ['sometimes', 'boolean'],
        ]);

        $team = $this->resolveTeam($data);
        $user = $this->provisioning->createForTeam($tournament, $team, [
            'name' => $data['name'],
            'email' => $data['email'],
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'],
            'is_disciplinary_committee' => $request->boolean('is_disciplinary_committee'),
        ]);

        return redirect()
            ->route('tournaments.show', ['tournament' => $tournament, 'tab' => 'delegados'])
            ->with(
                'status',
                "Delegado {$user->name} creado y vinculado a {$team->name}. Contraseña = documento {$user->document_number}."
            );
    }

    public function updateCommittee(Request $request, Tournament $tournament, User $user): RedirectResponse
    {
        $this->authorize('invite', $tournament);

        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'is_disciplinary_committee' => ['required', 'boolean'],
        ]);

        $team = Team::query()->findOrFail($data['team_id']);
        abort_unless($tournament->teams()->where('teams.id', $team->id)->exists(), 422);
        abort_unless($team->delegates()->where('users.id', $user->id)->exists(), 404);

        $team->delegates()->updateExistingPivot($user->id, [
            'is_disciplinary_committee' => $request->boolean('is_disciplinary_committee'),
        ]);

        return back()->with('status', 'Permisos de comité actualizados.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveTeam(array $data): Team
    {
        $newName = trim((string) ($data['new_team_name'] ?? ''));

        if ($newName !== '') {
            $existing = Team::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($newName)])->first();

            return $existing ?: Team::create(['name' => $newName]);
        }

        if (! empty($data['team_id'])) {
            return Team::query()->findOrFail($data['team_id']);
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'team_id' => 'Elegí un equipo o escribí el nombre de uno nuevo.',
        ]);
    }
}
