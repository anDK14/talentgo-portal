<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VacancyStatus extends Model
{
    use HasFactory;

    protected $fillable = ['status_name'];

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class, 'status_id');
    }
}