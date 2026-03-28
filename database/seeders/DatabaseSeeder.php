<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            AdminSeeder::class,
            InstitusiSeeder::class,
            MasterPerbekalanSeeder::class,
            MasterIkanSeeder::class,
            FisherySeeder::class,
            StokIkanSeeder::class,
            MasterCustomerSeeder::class,
            MasterItemPembelianSeeder::class,
            MasterOperasionalSeeder::class,
            MasterOperasionalKantorSeeder::class,
        ]);
    }
}
