<?php

namespace App\Observers;

use App\Models\VacancySubmission;
use App\Models\VacancyStatus;

class VacancySubmissionObserver
{
    public function creating(VacancySubmission $submission): void
    {
        // Auto-update vacancy status ke On-Process ketika kandidat pertama diajukan
        $vacancy = $submission->vacancy;

        if ($vacancy->status_id === 1) { // Jika status masih Open
            $onProcessStatus = VacancyStatus::where('status_name', 'On-Process')->first();
            if ($onProcessStatus) {
                $vacancy->update(['status_id' => $onProcessStatus->id]);
            }
        }
    }

    public function deleted(VacancySubmission $submission): void
    {
        // Check jika semua submissions untuk vacancy ini sudah dihapus
        $vacancy = $submission->vacancy;
        $remainingSubmissions = $vacancy->submissions()->count();

        // Jika tidak ada submissions lagi dan status bukan Closed, revert ke Open
        if ($remainingSubmissions === 0 && $vacancy->status_id !== 3) { // 3 = Closed
            $openStatus = VacancyStatus::where('status_name', 'Open')->first();
            if ($openStatus) {
                $vacancy->update(['status_id' => $openStatus->id]);
            }
        }
    }

    public function forceDeleted(VacancySubmission $submission): void
    {
        // Same logic as deleted() for force delete
        $vacancy = $submission->vacancy;
        $remainingSubmissions = $vacancy->submissions()->withTrashed()->count();

        if ($remainingSubmissions === 0 && $vacancy->status_id !== 3) {
            $openStatus = VacancyStatus::where('status_name', 'Open')->first();
            if ($openStatus) {
                $vacancy->update(['status_id' => $openStatus->id]);
            }
        }
    }
}
