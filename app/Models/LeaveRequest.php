<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date',
        'total_days', 'reason', 'attachment_path', 'status',
        'requested_at', 'approver_id', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'decimal:2',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvalActions(): MorphMany
    {
        return $this->morphMany(ApprovalAction::class, 'approvable');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED], true);
    }
}
