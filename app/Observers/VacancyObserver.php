<?php

namespace App\Observers;

use App\Models\Vacancy;
use App\Models\VacancyStatus;

class VacancyObserver
{
    public function updating(Vacancy $vacancy): void
    {
        // Auto-update status berdasarkan kondisi
        $submissionsCount = $vacancy->submissions()->count();
        
        if ($submissionsCount > 0 && $vacancy->status_id == 1) { // Open → On-Process
            $onProcessStatus = VacancyStatus::where('status_name', 'On-Process')->first();
            if ($onProcessStatus) {
                $vacancy->status_id = $onProcessStatus->id;
            }
        }
    }
}