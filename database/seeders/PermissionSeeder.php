<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Daftar semua permission
        $permissions = [
            'create user', 'read user', 'update user', 'delete user',
            'create purchase', 'read purchase', 'update purchase', 'delete purchase',
            'create master', 'read master', 'update master', 'delete master',

        ];

        // Buat permissions
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'admin',
            'kasir',
            'staff',
        ];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'kasir' => [
                'create purchase', 'read purchase', 'update purchase', 'delete purchase',
            ],
            'staff' => [
                'create purchase', 'read purchase', 'update purchase', 'delete purchase',
                'create master', 'read master', 'update master', 'delete master',
            ],
            'admin' => [
                'create user', 'read user', 'update user', 'delete user',
                'create purchase', 'read purchase', 'update purchase', 'delete purchase',
                'create master', 'read master', 'update master', 'delete master',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            $role->syncPermissions($perms);
        }

    }
}
