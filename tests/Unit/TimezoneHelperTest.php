<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;

class TimezoneHelperTest extends TestCase
{
    public function test_tz_returns_configured_display_timezone(): void
    {
        $this->assertSame(config('app.display_timezone'), tz());
        $this->assertSame('Asia/Jakarta', tz());
    }

    public function test_tz_now_and_today_use_display_timezone(): void
    {
        $this->assertSame('Asia/Jakarta', tz_now()->timezoneName);
        $this->assertSame('Asia/Jakarta', tz_today()->timezoneName);
    }

    public function test_to_tz_converts_utc_to_display_timezone(): void
    {
        $utc = Carbon::parse('2026-10-13 00:00:00', 'UTC');

        $local = to_tz($utc);

        $this->assertSame('2026-10-13 07:00:00', $local->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Jakarta', $local->timezoneName);
    }

    public function test_to_tz_handles_null(): void
    {
        $this->assertNull(to_tz(null));
        $this->assertNull(to_tz(''));
    }
}
