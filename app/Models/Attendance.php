<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STARTER = 'starter';
    public const SUBSTITUTE = 'substitute';
    public const PRESENT = 'present';
    public const ABSENT = 'absent';
    public const INJURED = 'injured';

    protected $fillable = [
        'game_id',
        'team_id',
        'player_id',
        'status',
        'minutes_played',
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
        return match ($this->status) {
            self::STARTER => 'Titular',
            self::SUBSTITUTE => 'Suplente',
            self::PRESENT => 'Presente',
            self::ABSENT => 'Ausente',
            self::INJURED => 'Lesionado',
            default => $this->status,
        };
    }

    public function countsAsPresent(): bool
    {
        return in_array($this->status, [self::STARTER, self::SUBSTITUTE, self::PRESENT], true);
    }
}
