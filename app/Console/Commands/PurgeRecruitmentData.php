<?php

namespace App\Console\Commands;

use App\Models\JobApplication;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeRecruitmentData extends Command
{
    protected $signature = 'recruitment:purge {--months= : Retensi dalam bulan} {--dry-run : Tampilkan tanpa menghapus}';

    protected $description = 'Menghapus data pelamar (ditolak/dibatalkan) yang melewati masa retensi beserta CV/foto.';

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?: config('recruitment.retention_months', 12));
        $threshold = now()->subMonths($months);
        $dry = (bool) $this->option('dry-run');

        $applications = JobApplication::whereIn('status', ['rejected', 'withdrawn'])
            ->where('created_at', '<', $threshold)
            ->get();

        if ($dry) {
            $this->info("Dry-run: {$applications->count()} lamaran akan dihapus (lebih lama dari {$months} bulan).");

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($applications as $application) {
            foreach ([$application->cv_path, $application->photo_path] as $path) {
                if ($path && Storage::disk('private')->exists($path)) {
                    Storage::disk('private')->delete($path);
                }
            }
            $application->delete();
            $deleted++;
        }

        AuditLogger::log('recruitment.purged', null, [], [
            'count' => $deleted,
            'retention_months' => $months,
        ]);

        $this->info("Selesai: {$deleted} lamaran dihapus (retensi {$months} bulan).");

        return self::SUCCESS;
    }
}
