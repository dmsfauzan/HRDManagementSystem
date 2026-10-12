<?php

namespace App\Console\Commands;

use App\Services\LeaveService;
use Illuminate\Console\Command;

class CarryOverLeaveBalance extends Command
{
    protected $signature = 'leave:carry-over {--year= : Tahun tujuan (default tahun berjalan)}';

    protected $description = 'Memindahkan sisa saldo cuti tahun sebelumnya ke tahun berjalan (dengan batas carry-over).';

    public function handle(LeaveService $service): int
    {
        $year = (int) ($this->option('year') ?: now('Asia/Jakarta')->format('Y'));

        $result = $service->carryOver($year);

        $this->info("Carry-over tahun {$year} selesai: {$result['carried']} saldo dipindahkan, {$result['expired']} kedaluwarsa.");

        return self::SUCCESS;
    }
}
