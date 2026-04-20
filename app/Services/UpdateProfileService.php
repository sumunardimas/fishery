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

            $user->email = $data['email'] ?? $user->email;

            if (Schema::hasColumn($profile->getTable(), 'name')) {
                $profile->name = $data['nama'] ?? $profile->name;
            }

            if (Schema::hasColumn($profile->getTable(), 'whatsapp')) {
                $profile->whatsapp = $data['whatsapp'] ?? $profile->whatsapp;
            }

            if (Schema::hasColumn($profile->getTable(), 'gender') && array_key_exists('gender', $data)) {
                $profile->gender = $data['gender'];
            }

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $documentUrl = null;
            if (! empty($data['document']) && Schema::hasColumn($profile->getTable(), 'document')) {
                $documentFile = $data['document'];

                $this->deleteStoredDocument($profile->document, $user->id);

                $profile->document = $documentFile->store('documents', 'local');
                $documentUrl = route('profile.document.show');
            }

            $profile->save();
            $user->save();

            return [
                'document_url' => $documentUrl,
            ];
        });
    }

    private function deleteStoredDocument(?string $path, int $userId): void
    {
        if (! empty($path)) {
            foreach (['local', 'public'] as $disk) {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);

                    return;
                }
            }
        }

        $legacyPath = 'users/profile/document/'.$userId;
        if (Storage::disk('local')->exists($legacyPath)) {
            Storage::disk('local')->delete($legacyPath);
        }
    }

}
