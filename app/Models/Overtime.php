<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Overtime extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id', 'date', 'start_at', 'end_at', 'duration_minutes',
        'reason', 'status', 'approver_id', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'decided_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvalActions(): MorphMany
    {
        return $this->morphMany(ApprovalAction::class, 'approvable');
    }

    public function startLabel(): string
    {
        return substr((string) $this->start_at, 0, 5);
    }

    public function endLabel(): string
    {
        return substr((string) $this->end_at, 0, 5);
    }
}
