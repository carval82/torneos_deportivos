<?php

namespace Database\Seeders;

use App\Models\AgeCategory;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            ['name' => 'Fútbol', 'slug' => 'futbol', 'scoring_unit' => 'goles', 'is_team_sport' => true, 'icon' => '⚽'],
            ['name' => 'Vóley', 'slug' => 'voley', 'scoring_unit' => 'sets', 'is_team_sport' => true, 'icon' => '🏐'],
            ['name' => 'Tenis', 'slug' => 'tenis', 'scoring_unit' => 'sets', 'is_team_sport' => false, 'icon' => '🎾'],
            ['name' => 'Básquet', 'slug' => 'basquet', 'scoring_unit' => 'puntos', 'is_team_sport' => true, 'icon' => '🏀'],
            ['name' => 'Handball', 'slug' => 'handball', 'scoring_unit' => 'goles', 'is_team_sport' => true, 'icon' => '🤾'],
            ['name' => 'Fútbol sala', 'slug' => 'futbol-sala', 'scoring_unit' => 'goles', 'is_team_sport' => true, 'icon' => '⚽'],
            ['name' => 'Fútbol 7', 'slug' => 'futbol-7', 'scoring_unit' => 'goles', 'is_team_sport' => true, 'icon' => '⚽'],
            ['name' => 'Fútbol 8', 'slug' => 'futbol-8', 'scoring_unit' => 'goles', 'is_team_sport' => true, 'icon' => '⚽'],
        ];

        foreach ($sports as $sport) {
            Sport::query()->updateOrCreate(
                ['slug' => $sport['slug']],
                $sport
            );
        }

        $categories = [
            ['name' => 'Sub-13', 'min_age' => 11, 'max_age' => 13, 'gender' => 'mixto'],
            ['name' => 'Sub-15', 'min_age' => 13, 'max_age' => 15, 'gender' => 'mixto'],
            ['name' => 'Sub-17', 'min_age' => 14, 'max_age' => 17, 'gender' => 'masculino'],
            ['name' => 'Primera', 'min_age' => 16, 'max_age' => null, 'gender' => 'mixto'],
            ['name' => 'Femenino libre', 'min_age' => null, 'max_age' => null, 'gender' => 'femenino'],
        ];

        foreach ($categories as $category) {
            AgeCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
