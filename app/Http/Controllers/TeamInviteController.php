<?php

namespace App\Http\Controllers;

use App\Models\TeamInvite;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeamInviteController extends Controller
{
    public function create(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('invite', $tournament);

        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        abort_unless($tournament->teams()->where('teams.id', $data['team_id'])->exists(), 422);

        $invite = TeamInvite::create([
            'tournament_id' => $tournament->id,
            'team_id' => $data['team_id'],
            'token' => TeamInvite::generateToken(),
            'email' => $data['email'] ?? null,
            'expires_at' => now()->addDays(14),
        ]);

        return back()->with('status', 'Link de delegado listo: '.$invite->url());
    }

    public function show(string $token): View
    {
        $invite = $this->findUsable($token);
        $invite->load(['tournament.sport', 'team']);

        return view('invites.show', [
            'invite' => $invite,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = $this->findUsable($token);

        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:150', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_DELEGATE,
                'email_verified_at' => now(),
            ]);

            Auth::login($user);
        }

        if (! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ORGANIZER], true)) {
            $user->update(['role' => User::ROLE_DELEGATE]);
        }

        $invite->team->delegates()->syncWithoutDetaching([
            $user->id => ['role' => 'delegate'],
        ]);

        $invite->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        return redirect()
            ->route('delegate.roster', ['team' => $invite->team_id, 'tournament' => $invite->tournament_id])
            ->with('status', 'Ya sos delegado de '.$invite->team->name.'. Podés cargar la plantilla.');
    }

    private function findUsable(string $token): TeamInvite
    {
        $invite = TeamInvite::query()->where('token', $token)->firstOrFail();

        abort_unless($invite->isUsable(), 410, 'Esta invitación ya no está disponible.');

        return $invite;
    }
}
