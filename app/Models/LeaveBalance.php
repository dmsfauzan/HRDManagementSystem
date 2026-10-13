<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'balance', 'used'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'balance' => 'decimal:2',
            'used' => 'decimal:2',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveBalanceTransaction::class);
    }
}
