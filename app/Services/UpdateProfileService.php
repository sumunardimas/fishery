<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UpdateProfileService
{
    /**
     * Update the given user's profile.
     *
     * @param  \App\Models\User $user
     * @param  array            $data
     * @return array{document_url?: string|null}
     */
    public function update(User $user, array $data): array
{
    return DB::transaction(function () use ($user, $data) {
        $profile = $user->getProfile();

        // update base user fields
        $user->email = $data['email'] ?? $user->email;

        // profile fields
        if (Schema::hasColumn($profile->getTable(), 'name')) {
            $profile->name = $data['nama'] ?? $profile->name;
        }
        if (Schema::hasColumn($profile->getTable(), 'whatsapp')) {
            $profile->whatsapp = $data['whatsapp'] ?? $profile->whatsapp;
        }
        if (Schema::hasColumn($profile->getTable(), 'gender') && array_key_exists('gender', $data)) {
            $profile->gender = $data['gender']; // "0"/"1" atau 0/1
        }

        // password only if provided (validated by FormRequest)
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // optional document upload -> store on profile (ONLY if column exists)
        $documentUrl = null;
        if (!empty($data['document']) && Schema::hasColumn($profile->getTable(), 'document')) {
            $documentFile = $data['document'];

            if (!empty($profile->document) && Storage::disk('public')->exists($profile->document)) {
                Storage::disk('public')->delete($profile->document);
            }

            $profile->document = $documentFile->store('documents', 'public');
            $documentUrl = Storage::disk('public')->url($profile->document);
        }

        $profile->save();
        $user->save();

        return [
            'document_url' => $documentUrl,
        ];
    });
}

}
