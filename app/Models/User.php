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
