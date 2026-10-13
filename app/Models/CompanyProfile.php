<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'tagline', 'about', 'logo_path', 'website', 'email', 'phone',
        'address', 'city', 'linkedin', 'instagram', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['name' => config('app.name', 'HRIS'), 'is_active' => true]
        );
    }
}
