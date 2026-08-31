<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suspension extends Model
{
    protected $fillable = [
        'tournament_id',
        'player_id',
        'team_id',
        'source_game_id',
        'source_event_id',
        'reason',
        'card_type',
        'matches_total',
        'matches_remaining',
        'is_active',
        'issued_by',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sourceGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'source_game_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function label(): string
    {
        return match ($this->card_type) {
            'red' => 'Roja directa',
            'double_yellow' => 'Doble amarilla',
            'yellow' => 'Amarilla',
            'committee' => 'Comité disciplinario',
            'manual' => 'Sanción manual',
            default => $this->card_type,
        };
    }
}
