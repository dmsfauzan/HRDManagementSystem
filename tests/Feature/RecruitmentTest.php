<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecruitmentTest extends TestCase
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

    public function test_public_can_view_and_apply_to_published_posting(): void
    {
        Storage::fake('private');
        Notification::fake();

        $posting = JobPosting::factory()->create();

        $this->get('/karir')->assertOk()->assertSee($posting->title);
        $this->get("/karir/{$posting->id}")->assertOk();

        $this->post("/karir/{$posting->id}/lamar", [
            'candidate_name' => 'Kandidat Satu',
            'candidate_email' => 'kandidat@example.com',
            'privacy_consent' => '1',
            'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $application = JobApplication::first();
        $this->assertNotNull($application);
        $this->assertNotNull($application->status_token);
        Storage::disk('private')->assertExists($application->cv_path);
        $this->assertDatabaseHas('application_stage_logs', ['job_application_id' => $application->id, 'to_status' => 'applied']);

        // Halaman status dapat diakses via token tanpa login.
        $this->get(route('career.status', $application->status_token))->assertOk()->assertSee('Kandidat Satu');
    }

    public function test_draft_posting_is_not_public(): void
    {
        $posting = JobPosting::factory()->draft()->create();

        $this->get("/karir/{$posting->id}")->assertNotFound();
        $this->get('/karir')->assertDontSee($posting->title);
    }

    public function test_apply_requires_cv(): void
    {
        $posting = JobPosting::factory()->create();

        $this->post("/karir/{$posting->id}/lamar", [
            'candidate_name' => 'Tanpa CV',
            'candidate_email' => 'nocv@example.com',
            'privacy_consent' => '1',
        ])->assertSessionHasErrors('cv');
    }

    public function test_hrd_can_create_and_publish_posting(): void
    {
        $hrd = $this->hrd();

        $this->actingAs($hrd)->post('/recruitment/postings', [
            'code' => 'JOB-TEST',
            'title' => 'Backend Developer',
            'description' => 'Deskripsi pekerjaan.',
            'work_mode' => 'remote',
            'experience_level' => 'mid',
            'employment_type' => 'permanent',
            'quota' => 2,
        ])->assertRedirect('/recruitment/postings');

        $posting = JobPosting::where('code', 'JOB-TEST')->first();
        $this->assertNotNull($posting);

        $this->actingAs($hrd)->patch("/recruitment/postings/{$posting->id}/publish")->assertRedirect();
        $this->assertSame('published', $posting->fresh()->status);
    }

    public function test_employee_cannot_access_recruitment(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('karyawan');

        $this->actingAs($user)->get('/recruitment/postings')->assertForbidden();
    }

    public function test_stage_machine_rejects_illegal_transition(): void
    {
        $hrd = $this->hrd();
        $application = JobApplication::factory()->create(['status' => 'applied']);

        $this->actingAs($hrd)->patch("/recruitment/applications/{$application->id}/stage", [
            'status' => 'hired',
        ])->assertRedirect();

        $this->assertSame('applied', $application->fresh()->status);
    }

    public function test_rejection_requires_reason(): void
    {
        $hrd = $this->hrd();
        $application = JobApplication::factory()->create(['status' => 'applied']);

        $this->actingAs($hrd)->patch("/recruitment/applications/{$application->id}/stage", [
            'status' => 'rejected',
        ])->assertRedirect();

        $this->assertSame('applied', $application->fresh()->status);
    }

    public function test_conversion_creates_employee_and_locks_application(): void
    {
        $hrd = $this->hrd();
        $department = Department::factory()->create(['code' => 'IT']);
        $posting = JobPosting::factory()->create(['department_id' => $department->id, 'employment_type' => 'permanent']);
        $application = JobApplication::factory()->create([
            'job_posting_id' => $posting->id,
            'candidate_name' => 'Kandidat Hired',
            'candidate_email' => 'hired@example.com',
            'status' => 'hired',
        ]);

        $response = $this->actingAs($hrd)->post("/recruitment/applications/{$application->id}/convert", [
            'join_date' => tz_today()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['full_name' => 'Kandidat Hired', 'email' => 'hired@example.com']);
        $this->assertNotNull($application->fresh()->employee_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'recruitment.converted']);
    }

    public function test_convert_rejected_for_non_hired_application(): void
    {
        $hrd = $this->hrd();
        $application = JobApplication::factory()->create(['status' => 'offer']);

        $this->actingAs($hrd)->post("/recruitment/applications/{$application->id}/convert")
            ->assertRedirect();

        $this->assertDatabaseCount('employees', 0);
        $this->assertNull($application->fresh()->employee_id);
    }
}
