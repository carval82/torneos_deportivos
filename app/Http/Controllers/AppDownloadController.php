<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppDownloadController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $path = public_path('app/arena-players.apk');
        abort_unless(is_file($path), 404, 'La app todavía no está publicada.');

        return response()->download($path, 'ArenaPlayers.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
