<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameReferee extends Model
{
    public const DUTY_MAIN = 'main';
    public const DUTY_ASSISTANT_1 = 'assistant_1';
    public const DUTY_ASSISTANT_2 = 'assistant_2';

    protected $fillable = [
        'game_id',
        'user_id',
        'duty',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dutyLabel(): string
    {
        return match ($this->duty) {
            self::DUTY_MAIN => 'Árbitro central',
            self::DUTY_ASSISTANT_1 => 'Asistente 1',
            self::DUTY_ASSISTANT_2 => 'Asistente 2',
            default => $this->duty,
        };
    }
}
