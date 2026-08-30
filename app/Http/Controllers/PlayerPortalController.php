<?php

namespace App\Http\Controllers;

use App\Services\PlayerAuthService;
use App\Services\PlayerPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlayerPortalController extends Controller
{
    public function __construct(
        private readonly PlayerAuthService $authService,
        private readonly PlayerPortalService $portal,
    ) {}

    public function create(): View
    {
        return view('player.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_number' => ['required', 'string', 'max:40'],
        ]);

        $user = $this->authService->attempt($data['document_number']);

        Auth::login($user, true);

        return redirect()->route('player.home');
    }

    public function home(Request $request): View
    {
        $player = $request->user()->player;
        abort_unless($player, 403, 'Tu usuario no está vinculado a un jugador.');

        return view('player.home', $this->portal->dashboard($player));
    }
}
