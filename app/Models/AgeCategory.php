<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgeCategory extends Model
{
    protected $fillable = [
        'name',
        'min_age',
        'max_age',
        'gender',
    ];

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function label(): string
    {
        $ages = collect([$this->min_age, $this->max_age])
            ->filter(fn ($age) => $age !== null)
            ->implode('–');

        return $ages !== ''
            ? "{$this->name} ({$ages} años)"
            : $this->name;
    }
}
