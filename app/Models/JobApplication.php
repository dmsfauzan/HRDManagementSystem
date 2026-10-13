<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    use HasFactory;

    public const STATUSES = ['applied', 'screening', 'interview', 'offer', 'hired', 'rejected', 'withdrawn'];

    protected $fillable = [
        'job_posting_id', 'candidate_name', 'candidate_email', 'candidate_phone',
        'domicile', 'birth_date', 'education', 'experience_years', 'source',
        'linkedin_url', 'portfolio_url', 'expected_salary',
        'cover_letter', 'cv_path', 'photo_path', 'status', 'rejection_reason',
        'status_token', 'employee_id', 'hired_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'experience_years' => 'integer',
            'expected_salary' => 'decimal:2',
            'hired_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(ApplicationStageLog::class)->orderBy('id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }
}
