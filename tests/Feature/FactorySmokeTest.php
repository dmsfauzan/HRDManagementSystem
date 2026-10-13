<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_create_valid_records(): void
    {
        $type = LeaveType::factory()->create();
        $this->assertDatabaseHas('leave_types', ['id' => $type->id]);

        $balance = LeaveBalance::factory()->create(['leave_type_id' => $type->id]);
        $this->assertDatabaseHas('leave_balances', ['id' => $balance->id]);

        $leave = LeaveRequest::factory()->create(['leave_type_id' => $type->id]);
        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id, 'status' => 'pending']);

        $attendance = AttendanceRecord::factory()->create();
        $this->assertDatabaseHas('attendance_records', ['id' => $attendance->id]);

        $announcement = Announcement::factory()->create();
        $this->assertTrue((bool) $announcement->is_active);

        $draft = Announcement::factory()->draft()->create();
        $this->assertFalse((bool) $draft->is_active);
        $this->assertNull($draft->published_at);
    }

    public function test_published_scope_excludes_draft(): void
    {
        Announcement::factory()->create(['title' => 'PUBLIK']);
        Announcement::factory()->draft()->create(['title' => 'DRAF']);

        $titles = Announcement::published()->pluck('title')->all();

        $this->assertContains('PUBLIK', $titles);
        $this->assertNotContains('DRAF', $titles);
    }
}
