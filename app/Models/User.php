<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin'; // master
    public const ROLE_ORGANIZER = 'organizer';
    public const ROLE_DELEGATE = 'delegate';
    public const ROLE_PLAYER = 'player';
    public const ROLE_REFEREE = 'referee';
    public const ROLE_REFEREE_COORDINATOR = 'referee_coordinator';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'document_type',
        'document_number',
        'phone',
        'player_id',
        'free_tournament_used',
        'tournament_credits',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'free_tournament_used' => 'boolean',
            'tournament_credits' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Alias de master / administrador de la plataforma. */
    public function isMaster(): bool
    {
        return $this->isAdmin();
    }

    public function isOrganizer(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ORGANIZER], true);
    }

    public function isDelegate(): bool
    {
        return $this->role === self::ROLE_DELEGATE || $this->teams()->exists();
    }

    public function isPlayer(): bool
    {
        return $this->role === self::ROLE_PLAYER || $this->player_id !== null;
    }

    public function isReferee(): bool
    {
        return $this->role === self::ROLE_REFEREE;
    }

    public function isRefereeCoordinator(): bool
    {
        return $this->role === self::ROLE_REFEREE_COORDINATOR;
    }

    public function isMatchOfficial(): bool
    {
        return in_array($this->role, [self::ROLE_REFEREE, self::ROLE_REFEREE_COORDINATOR], true)
            || $this->isAdmin();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Master',
            self::ROLE_ORGANIZER => 'Organizador',
            self::ROLE_DELEGATE => 'Delegado',
            self::ROLE_PLAYER => 'Jugador',
            self::ROLE_REFEREE => 'Árbitro',
            self::ROLE_REFEREE_COORDINATOR => 'Coordinador arbitral',
            default => $this->role,
        };
    }

    public function coordinatedTournaments(): HasMany
    {
        return $this->hasMany(Tournament::class, 'referee_coordinator_id');
    }

    public function officiatedGames(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_referees')
            ->withPivot('duty')
            ->withTimestamps();
    }

    public static function normalizeDocument(?string $document): ?string
    {
        $value = preg_replace('/\s+/', '', trim((string) $document));

        return $value === '' ? null : $value;
    }

    public function canAssignReferees(?Tournament $tournament): bool
    {
        if (! $tournament) {
            return false;
        }
        if ($this->isAdmin()) {
            return true;
        }
        if ($tournament->isReadOnly()) {
            return false;
        }
        if ((int) $tournament->user_id === (int) $this->id) {
            return true;
        }

        return $this->isRefereeCoordinator()
            && (int) $tournament->referee_coordinator_id === (int) $this->id;
    }

    public function canManageMatchSheet(Game $game): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $tournament = $game->relationLoaded('tournament')
            ? $game->tournament
            : $game->tournament()->first();

        if ($tournament?->isReadOnly()) {
            return false;
        }

        if ($tournament && $this->canAssignReferees($tournament)) {
            return true;
        }

        if ($game->relationLoaded('referees')) {
            return $game->referees->contains(fn (User $official) => (int) $official->id === (int) $this->id);
        }

        return $game->referees()->where('users.id', $this->id)->exists();
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function tournamentPayments(): HasMany
    {
        return $this->hasMany(TournamentPayment::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot(['role', 'is_disciplinary_committee'])
            ->withTimestamps();
    }

    public function managesTeam(int $teamId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->teams()->where('teams.id', $teamId)->exists();
    }

    public function canIssueDisciplinarySentence(Tournament $tournament): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ((int) $tournament->user_id === (int) $this->id) {
            return true;
        }

        return $this->teams()
            ->wherePivot('is_disciplinary_committee', true)
            ->whereHas('tournaments', fn ($q) => $q->where('tournaments.id', $tournament->id))
            ->exists();
    }
}
