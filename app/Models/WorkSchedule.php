<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'check_in_time', 'check_out_time', 'late_tolerance_minutes', 'work_days', 'crosses_midnight', 'is_active'];

    protected function casts(): array
    {
        return [
            'late_tolerance_minutes' => 'integer',
            'work_days' => 'array',
            'crosses_midnight' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public const WORK_DAYS_WEEKDAYS = [1, 2, 3, 4, 5];

    public function isWorkDay(int $weekday): bool
    {
        return in_array($weekday, $this->work_days ?? [], true);
    }
}
