<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            NewRoleSeeder::class,
            AdminUserSeeder::class,
            BellTimingSeeder::class,
            ClassSeeder::class,
            TeacherSeeder::class,
            StudentUserSeeder::class,
            TeacherAvailabilitySeeder::class,
            TeacherSubstitutionSeeder::class,
        ]);
    }
}
