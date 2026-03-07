<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Services\UpdateProfileService;
use App\Http\Requests\UpdateProfileRequest;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateProfileController extends Controller
{
    protected UpdateProfileService $updateProfileService;

    public function __construct(UpdateProfileService $updateProfileService)
    {
        $this->updateProfileService = $updateProfileService;
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            $result = $this->updateProfileService->update($request->user(), $request->all());

            // Jika request dari axios, kirim JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Profil berhasil diperbarui.',
                    'data' => $result, // { document_url: ... }
                ]);
            }

            // fallback normal submit
            return back()->with('status', 'Profil berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan server.',
                ], 500);
            }

            return back()->withErrors(['message' => 'Terjadi kesalahan server.'])->withInput();
        }
    }
}
