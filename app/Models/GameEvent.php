<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameEvent extends Model
{
    public const TYPE_GOAL = 'goal';
    public const TYPE_OWN_GOAL = 'own_goal';
    public const TYPE_ASSIST = 'assist';
    public const TYPE_YELLOW = 'yellow';
    public const TYPE_RED = 'red';
    public const TYPE_SUBSTITUTION = 'substitution';

    protected $fillable = [
        'game_id',
        'team_id',
        'player_id',
        'type',
        'minute',
        'note',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_GOAL => 'Gol',
            self::TYPE_OWN_GOAL => 'Gol en contra',
            self::TYPE_ASSIST => 'Asistencia',
            self::TYPE_YELLOW => 'Amarilla',
            self::TYPE_RED => 'Roja',
            self::TYPE_SUBSTITUTION => 'Cambio',
            default => $this->type,
        };
    }
}
