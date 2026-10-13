<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
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
}
