<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        return view('teams.index', [
            'teams' => Team::withCount(['players', 'tournaments'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('teams.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $team = Team::create($this->validated($request));

        return redirect()
            ->route('teams.show', $team)
            ->with('status', 'Equipo creado. Ahora cargá la planilla de jugadores.');
    }

    public function show(Team $team): View
    {
        $team->load(['players', 'tournaments.sport']);

        return view('teams.show', compact('team'));
    }

    public function edit(Team $team): View
    {
        return view('teams.edit', compact('team'));
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $this->validated($request, $team);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('teams', 'public');
        }

        $team->update($data);

        return redirect()
            ->route('teams.show', $team)
            ->with('status', 'Equipo actualizado.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()->route('teams.index')->with('status', 'Equipo eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Team $team = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'short_name' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:80'],
            'coach' => ['nullable', 'string', 'max:120'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        unset($data['logo']);

        if ($request->hasFile('logo') && ! $team) {
            $data['logo_path'] = $request->file('logo')->store('teams', 'public');
        }

        return $data;
    }
}
