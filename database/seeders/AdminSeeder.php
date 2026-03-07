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
        // Main admin account
        $user = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $user->assignRole('admin');

        Admin::factory()->create([
            'user_id' => $user->id,
        ]);

        // Additional admin(s)
        User::factory(1)->create([
            'password' => Hash::make('password'),
        ])->each(function ($user) {
            $user->assignRole('admin');

            Admin::factory()->create([
                'user_id' => $user->id,
            ]);
        });
    }
}
