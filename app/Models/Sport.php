<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'scoring_unit',
        'is_team_sport',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'is_team_sport' => 'boolean',
        ];
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
}
