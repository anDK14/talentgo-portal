<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'candidate_id',
        'submission_status_id',
        'submitted_by_user_id',
        'client_feedback'
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SubmissionStatus::class, 'submission_status_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }
}