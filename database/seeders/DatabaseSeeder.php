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
            // Fundamental access setup
            PermissionSeeder::class,
            AdminSeeder::class,

            // Supporting base model
            InstitusiSeeder::class,

            // Core masters (menu-driven order)
            MasterIkanSeeder::class,
            MasterIkanTangkapanSeeder::class,
            MasterIkanTangkapanRelationSeeder::class,
            MasterItemPembelianSeeder::class,
            MasterOperasionalSeeder::class,
            MasterPerbekalanSeeder::class,

            // Other master menus
            MasterCustomerSeeder::class,
            MasterOperasionalKantorSeeder::class,
        ]);
    }
}
