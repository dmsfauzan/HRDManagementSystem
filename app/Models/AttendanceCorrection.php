<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AttendanceCorrection extends Model
{
    protected $fillable = [
        'attendance_record_id', 'employee_id', 'work_date',
        'requested_check_in_at', 'requested_check_out_at',
        'reason', 'status', 'approved_by', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'requested_check_in_at' => 'datetime',
            'requested_check_out_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalActions(): MorphMany
    {
        return $this->morphMany(ApprovalAction::class, 'approvable');
    }
}
