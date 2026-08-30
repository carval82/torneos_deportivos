<?php

namespace Database\Seeders;

use App\Models\AgeCategory;
use App\Models\Player;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\Tournament;
use App\Models\User;
use App\Services\FixtureGenerator;
use App\Services\MatchSheetService;
use App\Support\Slug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->create([
            'name' => 'Pablo Capacho',
            'email' => 'pcapacho24@gmail.com',
            'password' => Hash::make('anaval33'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $sports = collect([
            ['name' => 'Fútbol', 'slug' => 'futbol', 'scoring_unit' => 'goles', 'is_team_sport' => true, 'icon' => '⚽'],
            ['name' => 'Vóley', 'slug' => 'voley', 'scoring_unit' => 'sets', 'is_team_sport' => true, 'icon' => '🏐'],
            ['name' => 'Tenis', 'slug' => 'tenis', 'scoring_unit' => 'sets', 'is_team_sport' => false, 'icon' => '🎾'],
            ['name' => 'Básquet', 'slug' => 'basquet', 'scoring_unit' => 'puntos', 'is_team_sport' => true, 'icon' => '🏀'],
            ['name' => 'Handball', 'slug' => 'handball', 'scoring_unit' => 'goles', 'is_team_sport' => true, 'icon' => '🤾'],
        ])->map(fn (array $sport) => Sport::create($sport));

        $categories = collect([
            ['name' => 'Sub-13', 'min_age' => 11, 'max_age' => 13, 'gender' => 'mixto'],
            ['name' => 'Sub-15', 'min_age' => 13, 'max_age' => 15, 'gender' => 'mixto'],
            ['name' => 'Sub-17', 'min_age' => 14, 'max_age' => 17, 'gender' => 'masculino'],
            ['name' => 'Primera', 'min_age' => 16, 'max_age' => null, 'gender' => 'mixto'],
            ['name' => 'Femenino libre', 'min_age' => null, 'max_age' => null, 'gender' => 'femenino'],
        ])->map(fn (array $category) => AgeCategory::create($category));

        $teamsData = [
            ['name' => 'Atlético Norte', 'short_name' => 'ATN', 'city' => 'Rosario', 'coach' => 'Mario Rivas', 'primary_color' => '#10b981'],
            ['name' => 'Deportivo Sur', 'short_name' => 'DSU', 'city' => 'Córdoba', 'coach' => 'Elena Paz', 'primary_color' => '#38bdf8'],
            ['name' => 'Club Central', 'short_name' => 'CEN', 'city' => 'Santa Fe', 'coach' => 'Hugo Méndez', 'primary_color' => '#fbbf24'],
            ['name' => 'Racing Juvenil', 'short_name' => 'RAC', 'city' => 'La Plata', 'coach' => 'Sofía Blanco', 'primary_color' => '#f472b6'],
            ['name' => 'Estrella Roja', 'short_name' => 'ESR', 'city' => 'Mendoza', 'coach' => 'Pablo Núñez', 'primary_color' => '#fb7185'],
            ['name' => 'Unidos FC', 'short_name' => 'UFC', 'city' => 'Tucumán', 'coach' => 'Laura Gómez', 'primary_color' => '#a78bfa'],
        ];

        $teams = collect($teamsData)->map(fn (array $team) => Team::create($team));
        $positions = ['Arquero', 'Defensor', 'Mediocampista', 'Delantero'];
        $document = 40111222;

        foreach ($teams as $teamIndex => $team) {
            for ($i = 1; $i <= 11; $i++) {
                $overAge = $teamIndex === 0 && $i === 11;
                $underAge = $teamIndex === 1 && $i === 11;
                $birth = match (true) {
                    $overAge => now()->subYears(19)->subDays(rand(10, 200)),
                    $underAge => now()->subYears(13)->subDays(rand(10, 80)),
                    default => now()->subYears(rand(15, 17))->subDays(rand(1, 300)),
                };

                Player::create([
                    'team_id' => $team->id,
                    'first_name' => fake('es_AR')->firstName('male'),
                    'last_name' => fake('es_AR')->lastName(),
                    'document_type' => 'DNI',
                    'document_number' => (string) ($document++),
                    'birthdate' => $birth,
                    'gender' => 'masculino',
                    'nationality' => 'Argentina',
                    'position' => $positions[($i - 1) % 4],
                    'jersey_number' => $i,
                    'phone' => '300'.str_pad((string) ($document - 1), 7, '0', STR_PAD_LEFT),
                ]);
            }
        }

        $football = $sports->firstWhere('slug', 'futbol');
        $sub17 = $categories->firstWhere('name', 'Sub-17');

        $tournament = Tournament::create([
            'user_id' => $admin->id,
            'sport_id' => $football->id,
            'age_category_id' => $sub17->id,
            'category_label' => 'Sub-17 complejo',
            'min_age' => 14,
            'max_age' => 17,
            'gender_rule' => 'masculino',
            'max_teams' => 20,
            'name' => 'Copa Arena Sub-17',
            'public_slug' => Slug::uniqueTournament('Copa Arena Sub-17'),
            'is_public' => true,
            'season' => '2026',
            'format' => Tournament::FORMAT_LEAGUE,
            'status' => Tournament::STATUS_INSCRIPTION,
            'start_date' => now()->subWeeks(3)->startOfWeek(\Carbon\Carbon::SUNDAY)->toDateString(),
            'end_date' => now()->addWeeks(12)->toDateString(),
            'double_round' => false,
            'venue' => 'Predio Arena',
            'complex_name' => 'Complejo Arena',
            'fields' => ['Cancha 1', 'Cancha 2', 'Cancha 3', 'Cancha 4', 'Cancha 5'],
            'play_days' => [0],
            'match_start_time' => '09:00',
            'match_interval_minutes' => 90,
            'days_between_rounds' => 7,
            'field_surface' => 'natural',
            'red_ban_matches' => 1,
            'double_yellow_ban_matches' => 1,
            'competition_rules' => [
                'walkover_goals_for' => 3,
                'walkover_goals_against' => 0,
                'max_no_shows_before_dq' => 2,
                'on_disqualification' => 'wo_remaining',
                'count_wo_in_standings' => true,
            ],
            'rules_published' => true,
            'rules_summary' => 'Solo domingos, césped natural. Doble amarilla = expulsión + 1 fecha.',
            'rules' => "1) Solo pueden jugar jugadores de 14 a 17 años al inicio del torneo.\n2) DNI o documento con foto obligatorio en planilla.\n3) Se juega los domingos en césped natural del Complejo Arena.\n4) Se juega cada domingo; si el clima posterga toda la fecha, se pasa al próximo domingo y se corren las siguientes.\n5) Dos amarillas en el mismo partido = expulsión + 1 fecha de sanción.\n6) Roja directa = 1 fecha de sanción (salvo que el reglamento indique más).\n7) Cada gol y tarjeta se cargan en el sistema.",
        ]);

        $sheets = app(MatchSheetService::class);

        foreach ($teams as $team) {
            $tournament->teams()->attach($team->id);
            $sheets->enrollTeamRoster($tournament->id, $team->id);
        }

        app(FixtureGenerator::class)->generate($tournament->fresh('teams'));

        $played = $tournament->games()->where('matchday', '<=', 2)->get();

        foreach ($played as $game) {
            $homePlayers = Player::query()->where('team_id', $game->home_team_id)->get();
            $awayPlayers = Player::query()->where('team_id', $game->away_team_id)->get();

            for ($i = 0; $i < rand(1, 3); $i++) {
                $sheets->addEvent($game, [
                    'team_id' => $game->home_team_id,
                    'player_id' => $homePlayers->random()->id,
                    'type' => 'goal',
                    'minute' => rand(8, 88),
                ]);
            }

            for ($i = 0; $i < rand(0, 2); $i++) {
                $sheets->addEvent($game, [
                    'team_id' => $game->away_team_id,
                    'player_id' => $awayPlayers->random()->id,
                    'type' => 'goal',
                    'minute' => rand(8, 88),
                ]);
            }

            $rows = $homePlayers->concat($awayPlayers)->map(fn (Player $player) => [
                'player_id' => $player->id,
                'team_id' => $player->team_id,
                'status' => $player->jersey_number <= 11 ? 'starter' : 'substitute',
                'minutes_played' => $player->jersey_number <= 11 ? rand(60, 90) : rand(0, 30),
            ])->all();

            $sheets->saveAttendance($game, $rows);
            $sheets->finish($game->fresh());
        }

        TeamInvite::create([
            'tournament_id' => $tournament->id,
            'team_id' => $teams->first()->id,
            'token' => TeamInvite::generateToken(),
            'expires_at' => now()->addDays(14),
        ]);

        Tournament::create([
            'user_id' => $admin->id,
            'sport_id' => $sports->firstWhere('slug', 'voley')->id,
            'category_label' => 'Sub-15 libre',
            'min_age' => 13,
            'max_age' => 15,
            'gender_rule' => 'mixto',
            'max_teams' => 12,
            'name' => 'Liga Arena Vóley Sub-15',
            'public_slug' => 'liga-arena-voley-sub-15',
            'is_public' => true,
            'season' => '2026',
            'format' => Tournament::FORMAT_LEAGUE,
            'status' => Tournament::STATUS_DRAFT,
            'start_date' => now()->addMonth()->toDateString(),
            'venue' => 'Polideportivo Central',
            'complex_name' => 'Polideportivo Central',
            'fields' => ['Cancha A', 'Cancha B', 'Cancha C'],
            'play_days' => [6, 0],
            'match_start_time' => '10:00',
            'match_interval_minutes' => 75,
            'rules_published' => true,
            'rules_summary' => 'Sábados y domingos. Edad 13 a 15. Tres canchas.',
            'rules' => 'Reglamento a completar por la organización antes de publicar fechas definitivas.',
        ]);
    }
}
