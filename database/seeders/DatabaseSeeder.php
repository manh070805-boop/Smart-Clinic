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
            RoleSeeder::class,
            ClinicSeeder::class,
            SpecialtySeeder::class,
            SymptomSeeder::class,
            UserAndDoctorSeeder::class,
            ScheduleSeeder::class,
        ]);
    }
}
