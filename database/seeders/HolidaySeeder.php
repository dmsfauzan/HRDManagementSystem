<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) now()->format('Y');

        $holidays = [
            ['month' => 1, 'day' => 1, 'name' => 'Tahun Baru Masehi'],
            ['month' => 5, 'day' => 1, 'name' => 'Hari Buruh Internasional'],
            ['month' => 8, 'day' => 17, 'name' => 'Hari Kemerdekaan Republik Indonesia'],
            ['month' => 12, 'day' => 25, 'name' => 'Hari Raya Natal'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => sprintf('%d-%02d-%02d', $year, $holiday['month'], $holiday['day'])],
                ['name' => $holiday['name'], 'is_national' => true]
            );
        }
    }
}
