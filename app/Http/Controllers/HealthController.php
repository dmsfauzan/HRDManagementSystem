<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDisk(),
            'scheduler' => $this->checkScheduler(),
        ];

        $healthy = ! in_array(false, array_column($checks, 'ok'), true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'time' => tz_now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('select 1');

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'database unavailable'];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

            return ['ok' => true, 'pending' => $pending, 'failed' => $failed];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'queue unavailable'];
        }
    }

    private function checkDisk(): array
    {
        try {
            $free = @disk_free_space(storage_path());
            $total = @disk_total_space(storage_path());
            $threshold = 100 * 1024 * 1024; // 100 MB

            return [
                'ok' => $free === false || $free > $threshold,
                'free_bytes' => $free === false ? null : (int) $free,
                'total_bytes' => $total === false ? null : (int) $total,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'disk unavailable'];
        }
    }

    private function checkScheduler(): array
    {
        try {
            $lastRun = Cache::get('scheduler_last_run');

            if (! $lastRun) {
                return ['ok' => true, 'last_run' => null, 'stale' => false, 'note' => 'not_run_yet'];
            }

            $last = Carbon::parse($lastRun);
            $stale = $last->lt(now()->subMinutes(15));

            return [
                'ok' => ! $stale,
                'last_run' => $last->toIso8601String(),
                'stale' => $stale,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'scheduler status unavailable'];
        }
    }
}
