<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'code', 'title', 'department_id', 'position_id', 'description', 'responsibilities',
        'requirements', 'benefits', 'location', 'work_mode', 'experience_level',
        'employment_type', 'quota', 'salary_min', 'salary_max', 'show_salary',
        'status', 'published_at', 'closes_at', 'closed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'show_salary' => 'boolean',
            'published_at' => 'datetime',
            'closes_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Lowongan masih menerima lamaran (terbit + belum melewati batas waktu).
     */
    public function isOpen(): bool
    {
        return $this->isPublished()
            && (! $this->closes_at || $this->closes_at->endOfDay()->isFuture());
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
