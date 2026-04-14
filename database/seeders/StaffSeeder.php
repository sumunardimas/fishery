<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Default staff account (idempotent)
        $user = User::query()->updateOrCreate([
            'username' => 'staff',
        ], [
            'email' => 'staff',
            'password' => Hash::make('password'),
        ]);

        $user->syncRoles(['staff']);

        Staff::query()->updateOrCreate([
            'user_id' => $user->id,
        ], [
            'name' => 'Staff',
        ]);
    }
}
