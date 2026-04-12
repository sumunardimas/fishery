<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Main admin account (idempotent)
        $user = User::query()->updateOrCreate([
            'username' => 'admin',
        ], [
            'email' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $user->assignRole('admin');

        Admin::query()->updateOrCreate([
            'user_id' => $user->id,
        ], [
            'name' => 'Administrator',
        ]);
    }
}
