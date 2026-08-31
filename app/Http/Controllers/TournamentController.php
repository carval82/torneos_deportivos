<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\CompetitionRulesService;
use App\Services\DisciplineService;
use App\Services\EligibilityChecker;
use App\Services\FixtureGenerator;
use App\Services\MatchdayScheduler;
use App\Services\MatchSheetService;
use App\Services\ProbabilityCalculator;
use App\Services\StandingCalculator;
use App\Services\TournamentBillingService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function __construct(
        private readonly StandingCalculator $standings,
        private readonly ProbabilityCalculator $probabilities,
        private readonly FixtureGenerator $fixtures,
        private readonly MatchSheetService $sheets,
        private readonly EligibilityChecker $eligibility,
        private readonly MatchdayScheduler $scheduler,
        private readonly DisciplineService $discipline,
        private readonly CompetitionRulesService $competitionRules,
        private readonly TournamentBillingService $billing,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Tournament::class);

        $query = Tournament::with('sport')->withCount(['teams', 'games'])->latest();
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return view('tournaments.index', [
            'tournaments' => $query->get(),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $this->authorize('create', Tournament::class);

        $user = Auth::user();
        if (! $this->billing->canCreateOrRenew($user)) {
            return redirect()
                ->route('billing.index')
                ->withErrors([
                    'billing' => 'Ya usaste tu torneo gratis. Para crear o renovar otro debés abonar $70.000 COP y esperar la aprobación del master.',
                ]);
        }

        return view('tournaments.create', array_merge($this->formData(), [
            'billingNote' => $user->isAdmin()
                ? 'Master: sin límite de torneos.'
                : ($user->free_tournament_used
                    ? 'Este torneo consumirá 1 crédito pago.'
                    : 'Este es tu torneo gratis incluido.'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Tournament::class);

        $billingType = $this->billing->consumeForCreate($request->user());

        $data = $this->validated($request);
        $data['user_id'] = Auth::id();
        $data['public_slug'] = Slug::uniqueTournament($data['name']);
        $data['is_public'] = false;
        $data['billing_type'] = $billingType;

        $tournament = Tournament::create($data);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Torneo creado ('.($billingType === 'free' ? 'gratis' : 'pago').'). Inscribí equipos y generá el fixture.');
    }

    public function renew(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $user = $request->user();
        abort_unless($user->isAdmin() || $tournament->user_id === $user->id, 403);

        if (! $this->billing->canCreateOrRenew($user)) {
            return redirect()
                ->route('billing.index')
                ->withErrors([
                    'billing' => 'Para renovar necesitás abonar $70.000 COP (1 torneo). Solicitá la activación al master.',
                ]);
        }

        $billingType = $this->billing->consumeForCreate($user);

        $replica = $tournament->replicate([
            'public_slug',
            'status',
            'start_date',
            'end_date',
        ]);
        $replica->name = $tournament->name.' · Renovación';
        $replica->season = (string) (now()->year);
        $replica->status = Tournament::STATUS_DRAFT;
        $replica->public_slug = Slug::uniqueTournament($replica->name);
        $replica->is_public = false;
        $replica->billing_type = $billingType;
        $replica->renewed_from_id = $tournament->id;
        $replica->user_id = $tournament->user_id;
        $replica->start_date = now()->toDateString();
        $replica->end_date = null;
        $replica->save();

        return redirect()
            ->route('tournaments.show', $replica)
            ->with('status', 'Torneo renovado ('.($billingType === 'free' ? 'gratis' : 'pago').'). Configurá fechas y generá el fixture.');
    }

    public function show(Request $request, Tournament $tournament): View
    {
        $this->authorize('view', $tournament);

        $tournament->load(['sport', 'teams.players', 'games.homeTeam', 'games.awayTeam', 'invites']);

        $tab = $request->string('tab', 'resumen')->toString();
        $table = $this->standings->table($tournament);
        $scorers = $this->standings->scorers($tournament);
        $curves = $this->standings->performanceCurves($tournament);
        $titleOdds = $this->probabilities->titleOdds($tournament);
        $attendance = $this->sheets->attendanceRanking($tournament->id);

        $availableTeams = Team::query()
            ->whereNotIn('id', $tournament->teams->pluck('id'))
            ->orderBy('name')
            ->get();

        $eligibility = [];
        foreach ($tournament->teams as $team) {
            foreach ($team->players as $player) {
                $eligibility[$player->id] = $this->eligibility->check($player, $tournament);
            }
        }

        $matchOdds = [];
        foreach ($tournament->games->where('status', '!=', 'finished') as $game) {
            $matchOdds[$game->id] = $this->probabilities->matchOdds($game);
        }

        return view('tournaments.show', [
            'tournament' => $tournament,
            'tab' => $tab,
            'table' => $table,
            'scorers' => $scorers,
            'curves' => $curves,
            'titleOdds' => $titleOdds,
            'attendance' => $attendance,
            'availableTeams' => $availableTeams,
            'eligibility' => $eligibility,
            'matchOdds' => $matchOdds,
            'gamesByMatchday' => $tournament->games
                ->sortBy([
                    ['matchday', 'asc'],
                    ['scheduled_at', 'asc'],
                    ['field_name', 'asc'],
                ])
                ->groupBy('matchday'),
            'suspensions' => $this->discipline->activeForTournament($tournament->id),
            'competitionRules' => $this->competitionRules->for($tournament),
            'competitionNarrative' => $this->competitionRules->narrative($tournament),
            'activeInvites' => $tournament->invites()
                ->whereNull('accepted_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->get()
                ->keyBy('team_id'),
        ]);
    }

    public function rules(Tournament $tournament): View
    {
        abort_unless($tournament->rules_published, 404);

        $tournament->load('sport');

        return view('tournaments.rules', [
            'tournament' => $tournament,
            'competitionRules' => $this->competitionRules->for($tournament),
            'competitionNarrative' => $this->competitionRules->narrative($tournament),
        ]);
    }

    public function edit(Tournament $tournament): View
    {
        $this->authorize('update', $tournament);

        return view('tournaments.edit', array_merge($this->formData(), [
            'tournament' => $tournament,
        ]));
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $data = $this->validated($request);
        if ($tournament->name !== $data['name'] || ! $tournament->public_slug) {
            $data['public_slug'] = Slug::uniqueTournament($data['name'], $tournament->id);
        }
        $data['is_public'] = $request->boolean('is_public', $tournament->is_public);

        $tournament->update($data);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Torneo actualizado.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $this->authorize('delete', $tournament);
        $tournament->delete();

        return redirect()
            ->route('tournaments.index')
            ->with('status', 'Torneo eliminado.');
    }

    public function enrollTeam(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('manage', $tournament);

        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
        ]);

        if ($tournament->max_teams && $tournament->teams()->count() >= $tournament->max_teams) {
            return back()->withErrors([
                'team_id' => "Este torneo admite como máximo {$tournament->max_teams} equipos.",
            ]);
        }

        $tournament->teams()->syncWithoutDetaching([$data['team_id']]);
        $this->sheets->enrollTeamRoster($tournament->id, (int) $data['team_id']);

        if ($tournament->status === Tournament::STATUS_DRAFT) {
            $tournament->update(['status' => Tournament::STATUS_INSCRIPTION]);
        }

        return back()->with('status', 'Equipo inscripto y plantel copiado a la planilla del torneo.');
    }

    public function generateFixture(Tournament $tournament): RedirectResponse
    {
        $this->authorize('manage', $tournament);

        try {
            $created = $this->fixtures->generate($tournament);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['fixture' => $exception->getMessage()]);
        }

        return redirect()
            ->route('tournaments.show', ['tournament' => $tournament, 'tab' => 'fixture'])
            ->with('status', "Fixture tentativo generado: {$created} partidos en {$tournament->playDaysLabel()}.");
    }

    public function resetFixture(Tournament $tournament): RedirectResponse
    {
        $this->authorize('manage', $tournament);

        $tournament->games()->delete();
        $tournament->snapshots()->delete();
        $tournament->update(['status' => Tournament::STATUS_INSCRIPTION]);

        return back()->with('status', 'Se borró el fixture. Podés generarlo de nuevo.');
    }

    public function postponeMatchday(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('manage', $tournament);

        $data = $request->validate([
            'matchday' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->scheduler->postponeMatchday(
                $tournament,
                (int) $data['matchday'],
                $data['reason'] ?: 'Postergada por clima / cancha natural'
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['fixture' => $exception->getMessage()]);
        }

        return redirect()
            ->route('tournaments.show', ['tournament' => $tournament, 'tab' => 'fixture'])
            ->with('status', "Fecha {$data['matchday']} corrida al {$result['new_date']} (+{$result['days']} días). Se movieron {$result['moved']} partidos y las fechas siguientes también se corrieron.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'sports' => Sport::orderBy('name')->get(),
            'weekdays' => Tournament::WEEKDAYS,
            'competitionRulesDefaults' => $this->competitionRules->defaults(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sport_id' => ['required', 'exists:sports,id'],
            'category_label' => ['nullable', 'string', 'max:80'],
            'min_age' => ['nullable', 'integer', 'min:5', 'max:80'],
            'max_age' => ['nullable', 'integer', 'min:5', 'max:80'],
            'gender_rule' => ['required', 'in:mixto,masculino,femenino'],
            'max_teams' => ['nullable', 'integer', 'min:2', 'max:128'],
            'season' => ['nullable', 'string', 'max:40'],
            'format' => ['required', 'in:league,knockout'],
            'status' => ['nullable', 'in:draft,inscription,ongoing,finished'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'double_round' => ['sometimes', 'boolean'],
            'venue' => ['nullable', 'string', 'max:150'],
            'complex_name' => ['nullable', 'string', 'max:150'],
            'fields_text' => ['nullable', 'string'],
            'play_days' => ['nullable', 'array'],
            'play_days.*' => ['integer', 'between:0,6'],
            'match_start_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'match_start_times' => ['nullable', 'array', 'min:1'],
            'match_start_times.*' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'match_interval_minutes' => ['nullable', 'integer', 'min:30', 'max:240'],
            'days_between_rounds' => ['nullable', 'integer', 'min:1', 'max:30'],
            'field_surface' => ['nullable', 'in:natural,artificial,mixed'],
            'red_ban_matches' => ['nullable', 'integer', 'min:1', 'max:10'],
            'double_yellow_ban_matches' => ['nullable', 'integer', 'min:1', 'max:10'],
            'walkover_goals_for' => ['nullable', 'integer', 'min:0', 'max:20'],
            'walkover_goals_against' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_no_shows_before_dq' => ['nullable', 'integer', 'min:1', 'max:20'],
            'on_disqualification' => ['nullable', 'in:wo_remaining,bye_rest'],
            'rules' => ['nullable', 'string'],
            'rules_summary' => ['nullable', 'string', 'max:500'],
            'rules_published' => ['sometimes', 'boolean'],
            'points_win' => ['nullable', 'integer', 'min:0', 'max:10'],
            'points_draw' => ['nullable', 'integer', 'min:0', 'max:10'],
            'points_loss' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        if (
            isset($data['min_age'], $data['max_age'])
            && $data['min_age'] !== null
            && $data['max_age'] !== null
            && (int) $data['max_age'] < (int) $data['min_age']
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'max_age' => 'La edad máxima no puede ser menor que la mínima.',
            ]);
        }

        $fields = collect(preg_split('/\r\n|\r|\n/', $data['fields_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $playDays = array_values(array_unique(array_map('intval', $data['play_days'] ?? [])));

        $data['fields'] = $fields !== [] ? $fields : null;
        $data['play_days'] = $playDays !== [] ? $playDays : [0];
        $data['double_round'] = $request->boolean('double_round');
        $data['rules_published'] = $request->boolean('rules_published');
        $data['match_interval_minutes'] = $data['match_interval_minutes'] ?? 90;
        $data['days_between_rounds'] = $data['days_between_rounds'] ?? 7;
        $data['field_surface'] = $data['field_surface'] ?? 'natural';
        $data['red_ban_matches'] = $data['red_ban_matches'] ?? 1;
        $data['double_yellow_ban_matches'] = $data['double_yellow_ban_matches'] ?? 1;
        $data['competition_rules'] = $this->competitionRules->normalize([
            'walkover_goals_for' => $data['walkover_goals_for'] ?? null,
            'walkover_goals_against' => $data['walkover_goals_against'] ?? null,
            'max_no_shows_before_dq' => $data['max_no_shows_before_dq'] ?? null,
            'on_disqualification' => $data['on_disqualification'] ?? 'wo_remaining',
            'count_wo_in_standings' => true,
        ]);

        $times = collect($data['match_start_times'] ?? [])
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->filter(fn ($time) => (bool) preg_match('/^\d{2}:\d{2}$/', $time))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($times === []) {
            $fallback = ! empty($data['match_start_time'])
                ? substr((string) $data['match_start_time'], 0, 5)
                : '09:00';
            $times = [$fallback];
        }

        $data['match_start_times'] = $times;
        $data['match_start_time'] = $times[0];

        unset(
            $data['fields_text'],
            $data['walkover_goals_for'],
            $data['walkover_goals_against'],
            $data['max_no_shows_before_dq'],
            $data['on_disqualification'],
        );

        return $data;
    }
}
