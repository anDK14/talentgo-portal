<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'status_id',
        'position_name',
        'level',
        'job_description',
        'required_skills'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(VacancyStatus::class, 'status_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(VacancySubmission::class);
    }
}