<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_paid', 'default_quota', 'tracks_balance', 'carry_max_days', 'requires_attachment', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'tracks_balance' => 'boolean',
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
            'default_quota' => 'decimal:2',
            'carry_max_days' => 'decimal:2',
        ];
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
