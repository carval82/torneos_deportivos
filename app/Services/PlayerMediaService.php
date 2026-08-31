<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PlayerMediaService
{
    public function storePhoto(Player $player, UploadedFile $file): string
    {
        $this->deleteIfExists($player->photo_path);
        $path = $file->store('players/photos', 'public');
        $player->update(['photo_path' => $path]);

        return $path;
    }

    public function storeDocumentPhoto(Player $player, UploadedFile $file): string
    {
        $this->deleteIfExists($player->document_photo_path);
        $path = $file->store('players/documents', 'public');
        $player->update(['document_photo_path' => $path]);

        return $path;
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
