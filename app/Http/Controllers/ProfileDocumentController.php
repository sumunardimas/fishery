<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProfileDocumentController extends Controller
{
    public function show(): Response
    {
        $user = auth()->user();
        $profile = $user?->getProfile();

        if (! $user || ! $profile || empty($profile->document)) {
            abort(404);
        }

        $documentPath = $this->resolveDocumentPath($user->id, $profile->document);
        if ($documentPath === null) {
            abort(404);
        }

        [$disk, $path, $filename] = $documentPath;

        return Storage::disk($disk)->download($path, $filename);
    }

    private function resolveDocumentPath(int $userId, string $storedValue): ?array
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($storedValue)) {
                return [$disk, $storedValue, basename($storedValue)];
            }
        }

        $legacyPath = 'users/profile/document/'.$userId;
        if (Storage::disk('local')->exists($legacyPath)) {
            return ['local', $legacyPath, $storedValue];
        }

        return null;
    }
}
