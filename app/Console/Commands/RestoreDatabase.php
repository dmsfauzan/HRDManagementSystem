<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {file? : Nama berkas di storage/app/backups atau path absolut} {--force : Tanpa konfirmasi}';

    protected $description = 'Memulihkan database dari berkas backup (.sql) menggunakan klien mysql.';

    public function handle(): int
    {
        $file = $this->resolveFile($this->argument('file'));

        if (! $file) {
            $this->error('Berkas backup tidak ditemukan di storage/app/backups.');

            return self::FAILURE;
        }

        if (config('database.default') !== 'mysql') {
            $this->warn('Restore hanya mendukung koneksi mysql. Dilewati.');

            return self::SUCCESS;
        }

        $this->warn("PERINGATAN: restore akan menimpa data pada database '".config('database.connections.mysql.database')."'.");
        $this->line('Berkas: '.$file);

        if (! $this->option('force') && ! $this->confirm('Lanjutkan restore?', false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $mysql = $this->findMysql();
        if (! $mysql) {
            $this->error('Klien mysql tidak ditemukan di PATH.');

            return self::FAILURE;
        }

        $db = config('database.connections.mysql');

        $process = new Process([
            $mysql,
            '--host='.$db['host'],
            '--port='.$db['port'],
            '--user='.$db['username'],
            ($db['password'] ? '--password='.$db['password'] : '--password='),
            $db['database'],
        ]);

        $process->setTimeout(1200);
        $process->setInput(fopen($file, 'r'));

        $output = '';
        $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;
        });

        if (! $process->isSuccessful()) {
            $this->error('Restore gagal: '.($output ?: $process->getErrorOutput()));

            return self::FAILURE;
        }

        AuditLogger::log('system.database_restored', null, [], ['file' => basename($file)]);
        $this->info('Restore berhasil dari '.basename($file));

        return self::SUCCESS;
    }

    private function resolveFile(?string $file): ?string
    {
        $dir = storage_path('app/backups');

        if ($file) {
            if (is_file($file)) {
                return $file;
            }

            $candidate = $dir.DIRECTORY_SEPARATOR.$file;

            return is_file($candidate) ? $candidate : null;
        }

        $files = glob($dir.DIRECTORY_SEPARATOR.'*.sql') ?: [];
        if ($files === []) {
            return null;
        }

        rsort($files);

        return $files[0];
    }

    private function findMysql(): ?string
    {
        foreach (['mysql', 'mysql.exe'] as $candidate) {
            $process = new Process([$candidate, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        $laragon = 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe';

        return is_file($laragon) ? $laragon : null;
    }
}
