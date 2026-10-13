<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'employee_id', 'full_name', 'email', 'phone',
        'department_id', 'position_id', 'manager_id', 'work_schedule_id',
        'join_date', 'contract_end_date', 'contract_reminded_at', 'employment_type', 'employment_status',
        'birth_date', 'gender', 'address', 'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'contract_end_date' => 'date',
            'contract_reminded_at' => 'datetime',
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    /**
     * Jadwal kerja efektif untuk tanggal tertentu (roster mingguan bila ada,
     * jika tidak kembali ke jadwal default karyawan).
     */
    public function scheduleForDate(CarbonInterface $date): ?WorkSchedule
    {
        $weekStart = Carbon::parse($date)->startOfWeek(CarbonInterface::MONDAY)->toDateString();

        $assigned = $this->scheduleAssignments()
            ->whereDate('week_start', $weekStart)
            ->with('workSchedule')
            ->first();

        return $assigned?->workSchedule ?? $this->workSchedule;
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    public function isManagerOf(Employee $employee): bool
    {
        return $employee->manager_id === $this->id;
    }

    public function hasSubordinate(Employee $employee): bool
    {
        return $this->subordinates()->whereKey($employee->id)->exists();
    }
}
