<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RefereeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizerRefereeController extends Controller
{
    public function __construct(private readonly RefereeService $referees) {}

    public function index(): View
    {
        $user = Auth::user();
        abort_unless(
            $user?->isAdmin() || $user?->isOrganizer() || $user?->isRefereeCoordinator(),
            403
        );

        $officials = User::query()
            ->whereIn('role', [User::ROLE_REFEREE, User::ROLE_REFEREE_COORDINATOR])
            ->withCount('officiatedGames')
            ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [User::ROLE_REFEREE_COORDINATOR])
            ->orderBy('name')
            ->get();

        return view('organizer.referees', [
            'officials' => $officials,
            'canCreateCoordinator' => $user->isAdmin() || $user->isOrganizer(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless(
            $user?->isAdmin() || $user?->isOrganizer() || $user?->isRefereeCoordinator(),
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'document_type' => ['required', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['required', 'string', 'max:40'],
            'role' => ['nullable', 'in:'.User::ROLE_REFEREE.','.User::ROLE_REFEREE_COORDINATOR],
        ]);

        $role = $data['role'] ?? User::ROLE_REFEREE;
        if ($role === User::ROLE_REFEREE_COORDINATOR && ! ($user->isAdmin() || $user->isOrganizer())) {
            abort(403, 'Solo el organizador o el master pueden crear coordinadores.');
        }

        $official = $this->referees->createOfficial([
            ...$data,
            'role' => $role,
        ]);

        $kind = $official->isRefereeCoordinator() ? 'Coordinador arbitral' : 'Árbitro';

        return redirect()
            ->route('organizer.referees.index')
            ->with(
                'status',
                "{$kind} {$official->name} listo. Contraseña inicial = documento {$official->document_number}."
            );
    }
}
