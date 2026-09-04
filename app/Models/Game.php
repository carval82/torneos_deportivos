<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_LIVE = 'live';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_POSTPONED = 'postponed';

    public const RESULT_PLAYED = 'played';
    public const RESULT_WALKOVER = 'walkover';
    public const RESULT_CANCELLED = 'cancelled';

    protected $fillable = [
        'tournament_id',
        'home_team_id',
        'away_team_id',
        'matchday',
        'round_name',
        'scheduled_at',
        'original_scheduled_at',
        'venue',
        'field_name',
        'is_tentative',
        'status',
        'result_type',
        'walkover_against_team_id',
        'walkover_reason',
        'home_score',
        'away_score',
        'notes',
        'postpone_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'original_scheduled_at' => 'datetime',
            'is_tentative' => 'boolean',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function walkoverAgainstTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'walkover_against_team_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(GameEvent::class)->orderBy('minute');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function referees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_referees')
            ->withPivot('duty')
            ->withTimestamps();
    }

    public function refereeAssignments(): HasMany
    {
        return $this->hasMany(GameReferee::class);
    }

    public function refereeOnDuty(string $duty): ?User
    {
        return $this->referees->firstWhere('pivot.duty', $duty);
    }

    public function refereesLabel(): string
    {
        if ($this->referees->isEmpty()) {
            return 'Sin árbitro';
        }

        return $this->referees
            ->map(fn (User $user) => $user->name)
            ->implode(' · ');
    }

    public function isWalkover(): bool
    {
        return $this->result_type === self::RESULT_WALKOVER;
    }

    public function statusLabel(): string
    {
        if ($this->isWalkover() && $this->status === self::STATUS_FINISHED) {
            return 'W.O.';
        }

        return match ($this->status) {
            self::STATUS_SCHEDULED => $this->is_tentative ? 'Tentativo' : 'Confirmado',
            self::STATUS_LIVE => 'En juego',
            self::STATUS_FINISHED => 'Finalizado',
            self::STATUS_POSTPONED => 'Aplazado',
            default => $this->status,
        };
    }

    public function walkoverReasonLabel(): string
    {
        return match ($this->walkover_reason) {
            'no_show' => 'No se presentó',
            'disqualification' => 'Descalificación',
            'admin' => 'Decisión de organización',
            default => $this->walkover_reason ?: 'W.O.',
        };
    }

    public function scoreline(): string
    {
        if ($this->home_score === null || $this->away_score === null) {
            return 'vs';
        }

        $line = "{$this->home_score} – {$this->away_score}";

        return $this->isWalkover() ? $line.' W.O.' : $line;
    }

    public function locationLabel(): string
    {
        return $this->field_name ?: ($this->venue ?: 'Sin cancha');
    }

    public function winnerTeamId(): ?int
    {
        if ($this->status !== self::STATUS_FINISHED || $this->home_score === null || $this->away_score === null) {
            return null;
        }

        if ($this->home_score === $this->away_score) {
            return null;
        }

        return $this->home_score > $this->away_score
            ? $this->home_team_id
            : $this->away_team_id;
    }
}
