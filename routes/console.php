<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:mark-absent')
    ->dailyAt('23:55')
    ->timezone('Asia/Jakarta');

Schedule::command('leave:carry-over')
    ->yearlyOn(1, 1, '02:00')
    ->timezone('Asia/Jakarta');

Schedule::command('contracts:remind')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta');

Schedule::command('db:backup')
    ->weeklyOn(0, '01:00')
    ->timezone('Asia/Jakarta');
