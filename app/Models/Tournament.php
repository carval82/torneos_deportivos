<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    public const FORMAT_LEAGUE = 'league';
    public const FORMAT_KNOCKOUT = 'knockout';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_INSCRIPTION = 'inscription';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_FINISHED = 'finished';

    public const WEEKDAYS = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    protected $fillable = [
        'user_id',
        'sport_id',
        'age_category_id',
        'category_label',
        'min_age',
        'max_age',
        'gender_rule',
        'max_teams',
        'name',
        'public_slug',
        'is_public',
        'season',
        'format',
        'status',
        'start_date',
        'end_date',
        'points_win',
        'points_draw',
        'points_loss',
        'double_round',
        'venue',
        'complex_name',
        'fields',
        'play_days',
        'match_start_time',
        'match_interval_minutes',
        'days_between_rounds',
        'field_surface',
        'red_ban_matches',
        'double_yellow_ban_matches',
        'competition_rules',
        'rules',
        'rules_published',
        'rules_summary',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'double_round' => 'boolean',
            'rules_published' => 'boolean',
            'is_public' => 'boolean',
            'fields' => 'array',
            'play_days' => 'array',
            'competition_rules' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function publicUrl(): ?string
    {
        return $this->public_slug ? route('public.tournaments.show', $this->public_slug) : null;
    }

    public function ageCategory(): BelongsTo
    {
        return $this->belongsTo(AgeCategory::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_team')
            ->withPivot(['group_name', 'seed', 'status', 'no_show_count', 'disqualified_at', 'disqualify_reason'])
            ->withTimestamps();
    }

    public function isTeamDisqualified(int $teamId): bool
    {
        $team = $this->teams->firstWhere('id', $teamId);

        return ($team?->pivot?->status ?? 'active') === 'disqualified';
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class)->orderBy('matchday')->orderBy('scheduled_at');
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(Roster::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(StandingSnapshot::class);
    }

    public function suspensions(): HasMany
    {
        return $this->hasMany(Suspension::class);
    }

    public function fieldSurfaceLabel(): string
    {
        return match ($this->field_surface) {
            'artificial' => 'Césped sintético',
            'mixed' => 'Mixta (natural / sintética)',
            default => 'Césped natural',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_INSCRIPTION => 'Inscripción',
            self::STATUS_ONGOING => 'En curso',
            self::STATUS_FINISHED => 'Finalizado',
            default => $this->status,
        };
    }

    public function formatLabel(): string
    {
        return match ($this->format) {
            self::FORMAT_LEAGUE => $this->double_round ? 'Liga (ida y vuelta)' : 'Liga (todos contra todos)',
            self::FORMAT_KNOCKOUT => 'Eliminación directa',
            default => $this->format,
        };
    }

    public function ageLabel(): string
    {
        if ($this->category_label) {
            return $this->category_label;
        }

        if ($this->min_age !== null || $this->max_age !== null) {
            $min = $this->min_age ?? '—';
            $max = $this->max_age ?? '—';

            return "{$min} a {$max} años";
        }

        return $this->ageCategory?->label() ?? 'Edad libre';
    }

    public function genderLabel(): string
    {
        return match ($this->gender_rule) {
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            default => 'Mixto',
        };
    }

    /**
     * @return list<string>
     */
    public function fieldList(): array
    {
        $fields = array_values(array_filter($this->fields ?? []));

        if ($fields !== []) {
            return $fields;
        }

        if ($this->venue) {
            return [$this->venue];
        }

        return ['Cancha 1'];
    }

    /**
     * @return list<int>
     */
    public function playDayList(): array
    {
        $days = collect($this->play_days ?? [])
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 0 && $day <= 6)
            ->unique()
            ->values()
            ->all();

        return $days !== [] ? $days : [0];
    }

    public function playDaysLabel(): string
    {
        return collect($this->playDayList())
            ->map(fn ($day) => self::WEEKDAYS[$day] ?? $day)
            ->implode(', ');
    }

    public function effectiveMinAge(): ?int
    {
        return $this->min_age ?? $this->ageCategory?->min_age;
    }

    public function effectiveMaxAge(): ?int
    {
        return $this->max_age ?? $this->ageCategory?->max_age;
    }

    public function effectiveGenderRule(): string
    {
        return $this->gender_rule ?: ($this->ageCategory?->gender ?? 'mixto');
    }
}
