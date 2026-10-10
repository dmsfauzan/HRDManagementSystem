<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            WorkScheduleSeeder::class,
            HolidaySeeder::class,
            LeaveTypeSeeder::class,
            OrganizationSeeder::class,
            DemoUserSeeder::class,
            DemoActivitySeeder::class,
        ]);
    }
}
