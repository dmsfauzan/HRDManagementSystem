<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function hrd(): User
    {
        $user = User::factory()->create();
        $user->assignRole('hrd_admin');

        return $user;
    }

    public function test_xlsx_export_returns_excel_file(): void
    {
        Employee::factory()->create(['full_name' => 'Ekspor Excel', 'employee_id' => 'EXP-1']);

        $response = $this->actingAs($this->hrd())->get('/reports/employees?format=xlsx');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Validasi berkas benar-benar XLSX (arsip ZIP dengan sheet).
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'XLSX bukan arsip ZIP yang valid.');
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertStringContainsString('Employee ID', $sheet);
        $this->assertStringContainsString('Ekspor Excel', $sheet);

        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported']);
    }

    public function test_print_export_returns_printable_view(): void
    {
        Employee::factory()->create(['full_name' => 'Cetak Laporan', 'employee_id' => 'EXP-2']);

        $response = $this->actingAs($this->hrd())->get('/reports/employees?format=print');

        $response->assertOk();
        $response->assertSee('Cetak Laporan');
        $response->assertSee('Cetak / Simpan PDF', false);
    }

    public function test_invalid_format_falls_back_to_html_page(): void
    {
        $response = $this->actingAs($this->hrd())->get('/reports/employees?format=bogus');

        $response->assertOk();
        $response->assertSee('Laporan Data Karyawan');
    }
}
