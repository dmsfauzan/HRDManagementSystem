<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    protected $fillable = [
        'job_application_id', 'scheduled_at', 'mode', 'location',
        'interviewers', 'result', 'score', 'notes', 'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'interviewers' => 'array',
            'score' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
