<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    protected $fillable = [
        'team_id',
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'birthdate',
        'gender',
        'nationality',
        'position',
        'jersey_number',
        'phone',
        'email',
        'photo_path',
        'document_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(GameEvent::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(Roster::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function normalizePhone(?string $phone = null): string
    {
        return preg_replace('/\D+/', '', $phone ?? $this->phone ?? '') ?? '';
    }

    public function fullName(): string
    {
        return trim("{$this->last_name}, {$this->first_name}");
    }

    public function displayName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function age(?CarbonInterface $at = null): ?int
    {
        if (! $this->birthdate) {
            return null;
        }

        return (int) $this->birthdate->diffInYears($at ?? now(), false);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    public function documentPhotoUrl(): ?string
    {
        return $this->document_photo_path ? asset('storage/'.$this->document_photo_path) : null;
    }
}
