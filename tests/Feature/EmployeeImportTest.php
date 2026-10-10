<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
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

    private function csv(array $rows): UploadedFile
    {
        $header = 'employee_id,full_name,email,phone,department_code,position_code,manager_employee_id,join_date,employment_type,employment_status';
        $content = $header."\n".implode("\n", $rows);

        return UploadedFile::fake()->createWithContent('employees.csv', $content);
    }

    public function test_imports_valid_rows(): void
    {
        Department::factory()->create(['code' => 'IT']);

        $response = $this->actingAs($this->hrd())->post('/employees/import', [
            'file' => $this->csv([
                'EMP-901,Andi Baru,andi@hris.test,081200000001,IT,,,2024-05-01,permanent,active',
                'EMP-902,Siti Baru,siti@hris.test,081200000002,IT,,,2024-05-02,contract,active',
            ]),
        ]);

        $response->assertRedirect('/employees/import');
        $this->assertDatabaseHas('employees', ['employee_id' => 'EMP-901', 'full_name' => 'Andi Baru']);
        $this->assertDatabaseCount('employees', 2);
    }

    public function test_reports_duplicate_and_invalid_rows(): void
    {
        Employee::factory()->create(['employee_id' => 'EMP-960']);

        $response = $this->actingAs($this->hrd())->post('/employees/import', [
            'file' => $this->csv([
                'EMP-960,Duplikat DB,,,IT,,,2024-05-01,permanent,active',
                'EMP-961,Duplikat File A,,,IT,,,2024-05-01,permanent,active',
                'EMP-961,Duplikat File B,,,IT,,,2024-05-01,permanent,active',
                ',Tanpa ID,,,IT,,,2024-05-01,permanent,active',
                'EMP-962,Tanggal Salah,,,IT,,,bukan-tanggal,permanent,active',
            ]),
        ]);

        $response->assertRedirect();
        $result = session('import_result');
        $this->assertSame(1, $result['imported']);
        $this->assertGreaterThanOrEqual(4, count($result['errors']));
        $this->assertDatabaseCount('employees', 2);
    }

    public function test_hrd_can_download_template(): void
    {
        $this->actingAs($this->hrd())->get('/employees/import/template')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_manager_cannot_import(): void
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        $this->actingAs($user)->get('/employees/import')->assertForbidden();
    }
}
