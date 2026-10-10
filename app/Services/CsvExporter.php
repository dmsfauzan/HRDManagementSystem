<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, is_array($row) ? $row : $row->toArray());
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function employeeHeaders(): array
    {
        return ['Employee ID', 'Nama', 'Email', 'Departemen', 'Jabatan', 'Atasan', 'Tgl Masuk', 'Tipe', 'Status'];
    }

    public static function attendanceHeaders(): array
    {
        return ['Tanggal', 'Employee ID', 'Nama', 'Departemen', 'Masuk', 'Keluar', 'Telat (menit)', 'Durasi (menit)', 'Status'];
    }

    public static function leaveHeaders(): array
    {
        return ['Karyawan', 'Jenis Cuti', 'Mulai', 'Selesai', 'Hari', 'Alasan', 'Status', 'Diputuskan Oleh', 'Tgl Keputusan'];
    }
}
