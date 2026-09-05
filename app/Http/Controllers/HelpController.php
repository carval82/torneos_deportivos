<?php

namespace App\Http\Controllers;

use App\Services\ArenaHelpDesk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function ask(Request $request, ArenaHelpDesk $help): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:400'],
        ]);

        $answer = $help->ask($data['message']);

        return response()->json([
            'title' => $answer['title'],
            'body' => $answer['body'],
            'suggestions' => $answer['suggestions'] ?? [],
        ]);
    }
}
