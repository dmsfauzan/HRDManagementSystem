<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function hrd(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('hrd_admin');

        return $user;
    }

    public function test_hrd_can_update_company_profile(): void
    {
        Storage::fake('private');
        $hrd = $this->hrd();

        $this->actingAs($hrd)->patch('/settings/company', [
            'name' => 'PT Maju Jaya',
            'tagline' => 'Tumbuh bersama',
            'city' => 'Bandung',
            'is_active' => true,
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ])->assertRedirect();

        $company = CompanyProfile::current();
        $this->assertSame('PT Maju Jaya', $company->name);
        $this->assertNotNull($company->logo_path);
        Storage::disk('private')->assertExists($company->logo_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'company_profile.updated']);
    }

    public function test_employee_cannot_access_company_settings(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('karyawan');

        $this->actingAs($user)->get('/settings/company')->assertForbidden();
    }

    public function test_career_index_filters_by_work_mode(): void
    {
        JobPosting::factory()->create(['title' => 'Remote Job', 'work_mode' => 'remote']);
        JobPosting::factory()->create(['title' => 'Onsite Job', 'work_mode' => 'onsite']);

        $this->get('/karir?work_mode=remote')
            ->assertOk()
            ->assertSee('Remote Job')
            ->assertDontSee('Onsite Job');
    }

    public function test_job_preview_shows_json_ld_structured_data(): void
    {
        $posting = JobPosting::factory()->create(['title' => 'Backend Engineer', 'work_mode' => 'remote']);

        $response = $this->get(route('career.show', $posting));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('JobPosting', false);
        $response->assertSee('TELECOMMUTE', false);
    }

    public function test_closed_posting_cannot_receive_application(): void
    {
        Storage::fake('private');
        $posting = JobPosting::factory()->create(['closes_at' => tz_today()->subDay()->toDateString()]);

        $this->post("/karir/{$posting->id}/lamar", [
            'candidate_name' => 'Terlambat',
            'candidate_email' => 'late@example.com',
            'privacy_consent' => '1',
            'cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseCount('job_applications', 0);
    }

    public function test_candidate_status_page_requires_valid_token(): void
    {
        $this->get('/karir/lamaran/token-tidak-ada')->assertNotFound();

        $application = JobApplication::factory()->create(['status_token' => 'validtoken123']);
        $this->get('/karir/lamaran/validtoken123')->assertOk();
    }

    public function test_multi_step_apply_accepts_extended_fields(): void
    {
        Storage::fake('private');
        $posting = JobPosting::factory()->create();

        $this->post("/karir/{$posting->id}/lamar", [
            'candidate_name' => 'Kandidat Lengkap',
            'candidate_email' => 'lengkap@example.com',
            'linkedin_url' => 'https://linkedin.com/in/kandidat',
            'portfolio_url' => 'https://portfolio.example.com',
            'expected_salary' => 12000000,
            'privacy_consent' => '1',
            'cv' => UploadedFile::fake()->create('cv.pdf', 60, 'application/pdf'),
        ])->assertRedirect();

        $application = JobApplication::first();
        $this->assertSame('https://linkedin.com/in/kandidat', $application->linkedin_url);
        $this->assertEquals(12000000, (float) $application->expected_salary);
    }

    public function test_hrd_posting_form_requires_work_mode_and_level(): void
    {
        $posting = JobPosting::factory()->create();

        $this->actingAs($this->hrd())->put("/recruitment/postings/{$posting->id}", [
            'code' => $posting->code,
            'title' => $posting->title,
            'description' => 'Deskripsi',
            // work_mode & experience_level sengaja kosong
            'employment_type' => 'permanent',
            'quota' => 1,
        ])->assertSessionHasErrors(['work_mode', 'experience_level']);
    }
}
