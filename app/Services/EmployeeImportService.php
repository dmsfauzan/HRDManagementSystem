<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeImportService
{
    private const REQUIRED = ['employee_id', 'full_name', 'join_date'];

    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = array_map(fn ($h) => str($h)->trim()->lower()->toString(), fgetcsv($handle) ?: []);

        if (empty($header)) {
            throw ValidationException::withMessages(['file' => 'Berkas CSV kosong atau header tidak terbaca.']);
        }

        foreach (self::REQUIRED as $col) {
            if (! in_array($col, $header, true)) {
                throw ValidationException::withMessages(['file' => "Kolom wajib tidak ditemukan: {$col}."]);
            }
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if (! array_filter($data, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }
            $rows[] = ['line' => $line, 'data' => (array) array_combine($header, array_slice(array_pad($data, count($header), ''), 0, count($header)))];
        }
        fclose($handle);

        $imported = 0;
        $errors = [];
        $existingEmployeeIds = Employee::pluck('employee_id')->all();
        $seenInFile = [];

        foreach ($rows as $row) {
            $lineNo = $row['line'];
            $data = $row['data'];
            $data = array_map('trim', $data);
            $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

            if (isset($data['employee_id'])) {
                $id = strtoupper($data['employee_id']);
                if (isset($seenInFile[$id])) {
                    $errors[] = "Baris {$lineNo}: employee_id {$id} duplikat di file (baris {$seenInFile[$id]}).";

                    continue;
                }
                $seenInFile[$id] = $lineNo;

                if (in_array($id, $existingEmployeeIds, true)) {
                    $errors[] = "Baris {$lineNo}: employee_id {$id} sudah ada di database.";

                    continue;
                }
            }

            if (empty($data['employee_id']) || empty($data['full_name'])) {
                $errors[] = "Baris {$lineNo}: employee_id dan full_name wajib diisi.";

                continue;
            }

            if (empty($data['join_date'])) {
                $errors[] = "Baris {$lineNo}: join_date wajib diisi (YYYY-MM-DD).";

                continue;
            }

            try {
                $data['join_date'] = Carbon::parse($data['join_date'])->toDateString();
            } catch (\Throwable $e) {
                $errors[] = "Baris {$lineNo}: join_date tidak valid.";

                continue;
            }

            try {
                DB::transaction(function () use ($data) {
                    $employee = Employee::create([
                        'employee_id' => strtoupper($data['employee_id']),
                        'full_name' => $data['full_name'],
                        'email' => $data['email'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'department_id' => isset($data['department_code']) && $data['department_code'] ? Department::where('code', $data['department_code'])->value('id') : null,
                        'position_id' => isset($data['position_code']) && $data['position_code'] ? Position::where('code', $data['position_code'])->value('id') : null,
                        'manager_id' => isset($data['manager_employee_id']) && $data['manager_employee_id'] ? Employee::where('employee_id', $data['manager_employee_id'])->value('id') : null,
                        'join_date' => $data['join_date'],
                        'employment_type' => $data['employment_type'] ?? 'permanent',
                        'employment_status' => $data['employment_status'] ?? 'active',
                    ]);
                    AuditLogger::log('employee.imported', $employee, [], ['source' => 'csv']);
                });
                $imported++;
            } catch (ValidationException $e) {
                $errors[] = 'Baris '.$lineNo.': '.implode(', ', collect($e->errors())->flatten()->all());
            } catch (\Throwable $e) {
                $errors[] = "Baris {$lineNo}: {$e->getMessage()}";
            }
        }

        if ($imported > 0) {
            AuditLogger::log('employees.imported', null, [], ['imported' => $imported, 'errors' => count($errors)]);
        }

        return ['imported' => $imported, 'errors' => $errors];
    }
}
