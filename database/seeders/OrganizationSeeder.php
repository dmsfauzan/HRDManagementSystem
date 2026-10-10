<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'IT', 'name' => 'Teknologi Informasi', 'description' => 'Pengembangan dan operasional sistem informasi.'],
            ['code' => 'HRD', 'name' => 'Sumber Daya Manusia', 'description' => 'Pengelolaan SDM dan administrasi kepegawaian.'],
            ['code' => 'FIN', 'name' => 'Keuangan', 'description' => 'Akuntansi dan pengelolaan keuangan.'],
            ['code' => 'MKT', 'name' => 'Pemasaran', 'description' => 'Pemasaran dan penjualan.'],
            ['code' => 'OPS', 'name' => 'Operasional', 'description' => 'Operasional harian perusahaan.'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(['code' => $dept['code']], $dept + ['is_active' => true]);
        }

        $positions = [
            ['code' => 'IT-MGR', 'name' => 'IT Manager', 'department' => 'IT', 'level' => 'Manager'],
            ['code' => 'IT-DEV', 'name' => 'Software Developer', 'department' => 'IT', 'level' => 'Staff'],
            ['code' => 'IT-SUP', 'name' => 'IT Support', 'department' => 'IT', 'level' => 'Staff'],
            ['code' => 'HRD-MGR', 'name' => 'HRD Manager', 'department' => 'HRD', 'level' => 'Manager'],
            ['code' => 'HRD-STAFF', 'name' => 'HRD Staff', 'department' => 'HRD', 'level' => 'Staff'],
            ['code' => 'FIN-MGR', 'name' => 'Finance Manager', 'department' => 'FIN', 'level' => 'Manager'],
            ['code' => 'FIN-STF', 'name' => 'Accounting Staff', 'department' => 'FIN', 'level' => 'Staff'],
            ['code' => 'MKT-STF', 'name' => 'Marketing Executive', 'department' => 'MKT', 'level' => 'Staff'],
            ['code' => 'OPS-STF', 'name' => 'Staff Operasional', 'department' => 'OPS', 'level' => 'Staff'],
        ];

        foreach ($positions as $pos) {
            $department = Department::where('code', $pos['department'])->first();
            Position::updateOrCreate(
                ['code' => $pos['code']],
                [
                    'name' => $pos['name'],
                    'department_id' => $department?->id,
                    'level' => $pos['level'],
                    'is_active' => true,
                ]
            );
        }
    }
}
