<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'city',
        'coach',
        'primary_color',
        'logo_path',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function delegates(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot(['role', 'is_disciplinary_committee'])
            ->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_team')
            ->withPivot(['group_name', 'seed', 'status', 'no_show_count', 'disqualified_at', 'disqualify_reason'])
            ->withTimestamps();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function initials(): string
    {
        if ($this->short_name) {
            return mb_strtoupper($this->short_name);
        }

        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}
