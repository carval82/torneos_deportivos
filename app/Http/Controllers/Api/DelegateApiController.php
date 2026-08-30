<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Roster;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DelegateApiController extends Controller
{
    public function teams(Request $request): JsonResponse
    {
        $user = $request->user();
        $teams = $user->isAdmin() || $user->isOrganizer()
            ? Team::withCount('players')->orderBy('name')->get()
            : $user->teams()->withCount('players')->orderBy('name')->get();

        return response()->json(['teams' => $teams]);
    }

    public function roster(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manageRoster', $team);

        return response()->json([
            'team' => $team->load('players'),
            'tournaments' => $team->tournaments()->get(['tournaments.id', 'name', 'public_slug', 'status']),
        ]);
    }

    public function storePlayer(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manageRoster', $team);

        $data = $request->validate([
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'document_type' => ['required', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['required', 'string', 'max:40'],
            'birthdate' => ['required', 'date'],
            'gender' => ['required', 'in:masculino,femenino,mixto'],
            'position' => ['nullable', 'string', 'max:60'],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        if (Player::query()
            ->where('document_type', $data['document_type'])
            ->where('document_number', $data['document_number'])
            ->exists()) {
            return response()->json(['message' => 'Ya existe un jugador con ese documento.'], 422);
        }

        $tournamentId = $data['tournament_id'] ?? null;
        unset($data['tournament_id']);

        $player = Player::create([
            ...$data,
            'team_id' => $team->id,
            'nationality' => 'Argentina',
        ]);

        if ($tournamentId) {
            Roster::updateOrCreate(
                ['tournament_id' => $tournamentId, 'player_id' => $player->id],
                [
                    'team_id' => $team->id,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'is_active' => true,
                ]
            );
        }

        return response()->json(['player' => $player], 201);
    }
}
