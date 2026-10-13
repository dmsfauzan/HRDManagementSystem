<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=14 : Jumlah berkas backup yang disimpan}';

    protected $description = 'Membuat backup database (mysqldump) ke storage/app/backups.';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->warn('Backup hanya mendukung koneksi mysql. Dilewati.');

            return self::SUCCESS;
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir.DIRECTORY_SEPARATOR.'hris_'.date('Ymd_His').'.sql';
        $db = config('database.connections.mysql');

        $mysqldump = $this->findMysqldump();
        if (! $mysqldump) {
            $this->error('mysqldump tidak ditemukan di PATH.');

            return self::FAILURE;
        }

        $process = new Process([
            $mysqldump,
            '--host='.$db['host'],
            '--port='.$db['port'],
            '--user='.$db['username'],
            ($db['password'] ? '--password='.$db['password'] : '--password='),
            $db['database'],
        ]);

        $process->setTimeout(600);
        $stdout = fopen($file, 'w');
        $process->run(function ($type, $buffer) use ($stdout) {
            fwrite($stdout, $buffer);
        });
        fclose($stdout);

        if (! $process->isSuccessful()) {
            @unlink($file);
            $this->error('Backup gagal: '.$process->getErrorOutput());

            return self::FAILURE;
        }

        $this->prune($dir, (int) $this->option('keep'));
        $this->info('Backup berhasil: '.$file);

        AuditLogger::log('system.database_backup', null, [], ['file' => basename($file)]);

        return self::SUCCESS;
    }

    private function findMysqldump(): ?string
    {
        foreach (['mysqldump', 'mysqldump.exe'] as $candidate) {
            $process = new Process([$candidate, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        $laragon = 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe';
        if (is_file($laragon)) {
            return $laragon;
        }

        return null;
    }

    private function prune(string $dir, int $keep): void
    {
        $files = glob($dir.DIRECTORY_SEPARATOR.'hris_*.sql') ?: [];
        sort($files);

        foreach (array_slice($files, 0, max(0, count($files) - $keep)) as $old) {
            @unlink($old);
        }
    }
}
