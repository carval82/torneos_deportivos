<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlayerAuthService;
use App\Services\PlayerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerAuthController extends Controller
{
    public function __construct(
        private readonly PlayerAuthService $authService,
        private readonly PlayerPortalService $portal,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_number' => ['required', 'string', 'max:40'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $this->authService->attempt($data['document_number']);
        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;
        $dashboard = $this->portal->dashboard($user->player);

        return response()->json([
            'token' => $token,
            'user' => $user->load('player.team'),
            'verified' => true,
            'document_number' => $user->player->document_number,
            'tournaments' => $dashboard['tournaments']->values(),
            'player' => $dashboard['player'],
            'upcoming' => $dashboard['upcoming']->values(),
            'results' => $dashboard['results']->values(),
            'team_standing' => $dashboard['teamStanding'],
        ]);
    }

    public function home(Request $request): JsonResponse
    {
        $player = $request->user()->player;
        abort_unless($player, 403);

        $dashboard = $this->portal->dashboard($player);

        return response()->json([
            'verified' => true,
            'document_number' => $player->document_number,
            'player' => $dashboard['player'],
            'tournaments' => $dashboard['tournaments']->values(),
            'upcoming' => $dashboard['upcoming']->values(),
            'results' => $dashboard['results']->values(),
            'team_standing' => $dashboard['teamStanding'],
            'tables' => $dashboard['tables'],
        ]);
    }
}
