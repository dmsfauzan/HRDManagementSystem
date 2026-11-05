<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HealthOpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_disk_and_scheduler_checks(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'time',
                'checks' => [
                    'database' => ['ok'],
                    'queue' => ['ok', 'pending', 'failed'],
                    'disk' => ['ok', 'free_bytes', 'total_bytes'],
                    'scheduler' => ['ok', 'last_run', 'stale'],
                ],
            ]);
    }

    public function test_health_scheduler_becomes_degraded_when_stale(): void
    {
        Cache::put('scheduler_last_run', now()->subHour()->toIso8601String(), 3600);

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJsonPath('checks.scheduler.ok', false);
    }

    public function test_restore_fails_when_backup_missing(): void
    {
        $this->artisan('db:restore', ['file' => 'tidak-ada.sql', '--force' => true])
            ->assertFailed();
    }
}
