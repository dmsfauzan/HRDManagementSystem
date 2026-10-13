<?php

use Carbon\Carbon;

if (! function_exists('tz')) {
    /**
     * Zona waktu tampilan aplikasi (default Asia/Jakarta).
     */
    function tz(): string
    {
        return (string) config('app.display_timezone', config('app.timezone', 'Asia/Jakarta'));
    }
}

if (! function_exists('tz_now')) {
    function tz_now(): Carbon
    {
        return Carbon::now(tz());
    }
}

if (! function_exists('tz_today')) {
    function tz_today(): Carbon
    {
        return Carbon::today(tz());
    }
}

if (! function_exists('to_tz')) {
    /**
     * Konversi nilai tanggal/waktu apa pun ke zona waktu tampilan.
     */
    function to_tz(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->timezone(tz());
    }
}
