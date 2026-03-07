<?php

namespace App\Services;

use App\Models\User;
use App\Models\Panitia;
use App\Models\Kasir;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterService
{
    public function store(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'whatsapp' => $data['whatsapp'],
                'password' => Hash::make($data['password']),
                'gender' => $data['gender'],
                'role' => $data['role'],
            ]);

            $documentPath = null;
            if (isset($data['document'])) {
                $documentPath = $data['document']->store('documents', 'public');
            }

            match ($data['role']) {
                'Admin' => Panitia::create([
                    'user_id' => $user->id,
                    'institution' => $data['institution'],
                    'document' => $documentPath,
                ]),
                'Kasir' => Kasir::create([
                    'user_id' => $user->id,
                    'institution' => $data['institution'],
                    'document' => $documentPath,
                ]),
                'Staff' => Staff::create([
                    'user_id' => $user->id,
                    'institution' => $data['institution'],
                    'document' => $documentPath,
                ]),
            };

            return $user;
        });
    }
}
