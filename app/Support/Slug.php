<?php

namespace App\Support;

use App\Models\Tournament;
use Illuminate\Support\Str;

class Slug
{
    public static function uniqueTournament(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'torneo';
        $slug = $base;
        $i = 2;

        while (
            Tournament::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('public_slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
