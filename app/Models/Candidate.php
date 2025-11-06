<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        // HAPUS 'unique_talent_id' dari fillable
        'full_name',
        'email',
        'phone_number',
        'linkedin_url',
        'portfolio_url',
        'experience_summary',
        'education_summary',
        'skills_summary',
        'is_available'
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(VacancySubmission::class);
    }

    // Accessor untuk Blind CV
    public function getBlindCvAttribute(): array
    {
        return [
            'unique_id' => $this->unique_talent_id,
            'experience' => $this->experience_summary,
            'skills' => $this->skills_summary,
            'education' => $this->education_summary,
            // Data pribadi sengaja tidak disertakan
        ];
    }
}