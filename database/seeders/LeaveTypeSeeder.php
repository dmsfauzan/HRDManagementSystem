<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true, 'default_quota' => 12, 'tracks_balance' => true, 'requires_attachment' => false],
            ['code' => 'sick', 'name' => 'Cuti Sakit', 'is_paid' => true, 'default_quota' => 0, 'tracks_balance' => false, 'requires_attachment' => true],
            ['code' => 'maternity', 'name' => 'Cuti Melahirkan', 'is_paid' => true, 'default_quota' => 90, 'tracks_balance' => true, 'requires_attachment' => true],
            ['code' => 'unpaid', 'name' => 'Cuti Tanpa Upah', 'is_paid' => false, 'default_quota' => 0, 'tracks_balance' => false, 'requires_attachment' => false],
            ['code' => 'permit', 'name' => 'Izin', 'is_paid' => true, 'default_quota' => 0, 'tracks_balance' => false, 'requires_attachment' => false],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['code' => $type['code']], $type + ['is_active' => true]);
        }
    }
}
