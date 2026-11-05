<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_duplicate_application_is_rejected(): void
    {
        Storage::fake('private');
        $posting = JobPosting::factory()->create();

        $payload = [
            'candidate_name' => 'Kandidat Ganda',
            'candidate_email' => 'ganda@example.com',
            'privacy_consent' => '1',
            'cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
        ];

        $this->post("/karir/{$posting->id}/lamar", $payload)->assertRedirect();
        $this->assertDatabaseCount('job_applications', 1);

        $second = $payload;
        $second['cv'] = UploadedFile::fake()->create('cv2.pdf', 50, 'application/pdf');
        $this->post("/karir/{$posting->id}/lamar", $second)->assertSessionHasErrors('candidate_email');

        $this->assertDatabaseCount('job_applications', 1);
    }

    public function test_privacy_consent_is_required(): void
    {
        Storage::fake('private');
        $posting = JobPosting::factory()->create();

        $this->post("/karir/{$posting->id}/lamar", [
            'candidate_name' => 'Tanpa Consent',
            'candidate_email' => 'noconsent@example.com',
            'cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('privacy_consent');

        $this->assertDatabaseCount('job_applications', 0);
    }

    public function test_privacy_page_is_public(): void
    {
        $this->get('/kebijakan-privasi')->assertOk()->assertSee('Kebijakan Privasi');
    }

    public function test_password_change_revokes_other_sessions(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password12345'),
            'is_active' => true,
        ]);

        DB::table('sessions')->insert([
            'id' => str_repeat('a', 40),
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Perangkat lain',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'Password12345',
            'password' => 'NewPassword1234',
            'password_confirmation' => 'NewPassword1234',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => str_repeat('a', 40)]);
    }

    public function test_failed_login_is_audited(): void
    {
        User::factory()->create([
            'email' => 'ada@hris.test',
            'password' => Hash::make('Password12345'),
            'is_active' => true,
        ]);

        $this->post('/login', ['email' => 'ada@hris.test', 'password' => 'salah-sekali']);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed']);
    }

    public function test_inactive_login_is_audited(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@hris.test',
            'password' => Hash::make('Password12345'),
            'is_active' => false,
        ]);

        $this->post('/login', ['email' => 'nonaktif@hris.test', 'password' => 'Password12345']);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed']);
    }

    public function test_application_stores_privacy_consent_timestamp(): void
    {
        Storage::fake('private');
        $posting = JobPosting::factory()->create();

        $this->post("/karir/{$posting->id}/lamar", [
            'candidate_name' => 'Consent OK',
            'candidate_email' => 'consent@example.com',
            'privacy_consent' => '1',
            'cv' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf'),
        ])->assertRedirect();

        $this->assertNotNull(JobApplication::first()->privacy_consented_at);
    }
}
