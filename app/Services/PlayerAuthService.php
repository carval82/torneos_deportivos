<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlayerAuthService
{
    public function __construct(private readonly PlayerPortalService $portal) {}

    public function attempt(string $documentNumber): User
    {
        $player = $this->portal->findByDocument($documentNumber);
        $tournaments = $this->portal->linkedTournaments($player);

        if ($tournaments->isEmpty()) {
            throw ValidationException::withMessages([
                'document_number' => 'Tu cédula está cargada, pero todavía no estás vinculado a ningún torneo. Pedile al delegado que te sume a la plantilla.',
            ]);
        }

        $user = User::query()->where('player_id', $player->id)->first()
            ?? User::query()->where('document_number', $player->document_number)->first();

        if (! $user) {
            $user = User::create([
                'name' => $player->displayName(),
                'email' => sprintf('player.%s@arena.players', $player->document_number),
                'password' => Hash::make(Str::random(32)),
                'role' => User::ROLE_PLAYER,
                'document_type' => $player->document_type ?: 'Cédula',
                'document_number' => $player->document_number,
                'phone' => $player->phone,
                'player_id' => $player->id,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'role' => User::ROLE_PLAYER,
                'player_id' => $player->id,
                'document_type' => $player->document_type ?: 'Cédula',
                'document_number' => $player->document_number,
                'phone' => $player->phone,
                'name' => $player->displayName(),
            ]);
        }

        return $user->fresh('player.team');
    }
}
