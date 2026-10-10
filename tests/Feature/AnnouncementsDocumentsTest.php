<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementsDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_hrd_can_publish_announcement_and_users_notified(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $karyawan = $this->userWithRole('karyawan');

        $response = $this->actingAs($hrd)->post('/announcements', [
            'title' => 'Libur Nasional',
            'body' => 'Kantor tutup tanggal 25 Desember.',
            'action' => 'publish',
        ]);

        $response->assertRedirect('/announcements');
        $this->assertDatabaseHas('announcements', ['title' => 'Libur Nasional', 'is_active' => true]);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $karyawan->id]);
    }

    public function test_draft_announcement_does_not_notify(): void
    {
        $hrd = $this->userWithRole('hrd_admin');

        $this->actingAs($hrd)->post('/announcements', [
            'title' => 'Draf Saja',
            'body' => 'Belum terbit.',
            'action' => 'draft',
        ]);

        $this->assertDatabaseHas('announcements', ['title' => 'Draf Saja', 'is_active' => false]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_karyawan_only_sees_published_announcements(): void
    {
        Announcement::create(['title' => 'Terbit', 'body' => 'A', 'is_active' => true, 'published_at' => now()]);
        Announcement::create(['title' => 'Draf Rahasia', 'body' => 'B', 'is_active' => false]);

        $response = $this->actingAs($this->userWithRole('karyawan'))->get('/announcements');

        $response->assertOk();
        $response->assertSee('Terbit');
        $response->assertDontSee('Draf Rahasia');
    }

    public function test_hrd_can_upload_document_privately(): void
    {
        Storage::fake('private');
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/documents', [
            'employee_id' => $employee->id,
            'title' => 'Kontrak Kerja',
            'file' => UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $document = Document::first();
        $this->assertNotNull($document);
        Storage::disk('private')->assertExists($document->file_path);
    }

    public function test_employee_can_only_see_and_download_own_documents(): void
    {
        Storage::fake('private');

        [$userA, $empA] = [User::factory()->create(), Employee::factory()->create()];
        $empA->update(['user_id' => $userA->id]);
        $userA->assignRole('karyawan');
        $empB = Employee::factory()->create();

        Storage::disk('private')->put('documents/a.pdf', 'aaa');
        Storage::disk('private')->put('documents/b.pdf', 'bbb');
        $docA = Document::create(['employee_id' => $empA->id, 'title' => 'Milik A', 'file_path' => 'documents/a.pdf']);
        $docB = Document::create(['employee_id' => $empB->id, 'title' => 'Milik B', 'file_path' => 'documents/b.pdf']);

        $index = $this->actingAs($userA)->get('/documents');
        $index->assertOk()->assertSee('Milik A')->assertDontSee('Milik B');

        $this->actingAs($userA)->get("/documents/{$docA->id}/download")->assertOk();
        $this->actingAs($userA)->get("/documents/{$docB->id}/download")->assertForbidden();
    }

    public function test_karyawan_cannot_upload_documents(): void
    {
        $user = $this->userWithRole('karyawan');
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/documents', [
            'employee_id' => $employee->id,
            'title' => 'Coba',
            'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }
}
