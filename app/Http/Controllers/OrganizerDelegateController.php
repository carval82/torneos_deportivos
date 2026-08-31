<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\DelegateProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizerDelegateController extends Controller
{
    public function __construct(private readonly DelegateProvisioningService $provisioning) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user?->isOrganizer() || $user?->isAdmin(), 403);

        $tournaments = Tournament::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->with(['teams.delegates', 'sport'])
            ->latest()
            ->get();

        $teams = Team::query()->orderBy('name')->get();

        $delegates = User::query()
            ->where('role', User::ROLE_DELEGATE)
            ->with(['teams' => fn ($q) => $q->withPivot(['role', 'is_disciplinary_committee'])])
            ->when(! $user->isAdmin(), function ($q) use ($tournaments) {
                $teamIds = $tournaments->flatMap->teams->pluck('id')
                    ->merge(Team::query()->pluck('id'))
                    ->unique()
                    ->filter();
                $q->whereHas('teams', fn ($teams) => $teams->whereIn('teams.id', $teamIds));
            })
            ->orderBy('name')
            ->get();

        return view('organizer.delegates', [
            'tournaments' => $tournaments,
            'teams' => $teams,
            'delegates' => $delegates,
            'selectedTournamentId' => $request->integer('tournament_id') ?: $tournaments->first()?->id,
            'selectedTeamId' => $request->integer('team_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user?->isOrganizer() || $user?->isAdmin(), 403);

        $data = $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'new_team_name' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'document_type' => ['required', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['required', 'string', 'max:40'],
            'is_disciplinary_committee' => ['sometimes', 'boolean'],
        ]);

        $tournament = Tournament::query()->findOrFail($data['tournament_id']);
        $this->authorize('invite', $tournament);

        $team = $this->resolveTeam($data);

        $delegate = $this->provisioning->createForTeam($tournament, $team, [
            'name' => $data['name'],
            'email' => $data['email'],
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'],
            'is_disciplinary_committee' => $request->boolean('is_disciplinary_committee'),
        ]);

        return redirect()
            ->route('organizer.delegates.index', ['tournament_id' => $tournament->id])
            ->with(
                'status',
                "Delegado {$delegate->name} creado y vinculado a {$team->name} en {$tournament->name}. Contraseña = documento {$delegate->document_number}."
            );
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

        throw ValidationException::withMessages([
            'team_id' => 'Elegí un equipo o escribí el nombre de uno nuevo.',
        ]);
    }
}
