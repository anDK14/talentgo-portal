<?php

namespace App\Observers;

use App\Models\Vacancy;
use App\Models\VacancyStatus;

class VacancyObserver
{
    public function updated(Vacancy $vacancy): void
    {
        // Auto-check submissions count ketika vacancy diupdate
        $submissionsCount = $vacancy->submissions()->count();
        
        // Jika tidak ada submissions dan status bukan Closed, set ke Open
        if ($submissionsCount === 0 && $vacancy->status_id !== 3) {
            $openStatus = VacancyStatus::where('status_name', 'Open')->first();
            if ($openStatus && $vacancy->status_id !== $openStatus->id) {
                // Use updateQuietly to prevent recursion
                $vacancy->updateQuietly(['status_id' => $openStatus->id]);
            }
        }
        
        // Jika ada submissions dan status masih Open, set ke On-Process
        if ($submissionsCount > 0 && $vacancy->status_id === 1) {
            $onProcessStatus = VacancyStatus::where('status_name', 'On-Process')->first();
            if ($onProcessStatus) {
                // Use updateQuietly to prevent recursion
                $vacancy->updateQuietly(['status_id' => $onProcessStatus->id]);
            }
        }
    }

    /**
     * Handle the Vacancy "deleting" event.
     * This handles both single delete and bulk delete
     */
    public function deleting(Vacancy $vacancy): void
    {
        // Delete all related submissions when vacancy is deleted
        $vacancy->submissions()->delete();
    }

    /**
     * Handle the Vacancy "created" event.
     * Set default status to Open if not set
     */
    public function creating(Vacancy $vacancy): void
    {
        if (empty($vacancy->status_id)) {
            $openStatus = VacancyStatus::where('status_name', 'Open')->first();
            if ($openStatus) {
                $vacancy->status_id = $openStatus->id;
            }
        }
    }

    /**
     * Handle the Vacancy "saved" event.
     * Additional logic after vacancy is saved
     */
    public function saved(Vacancy $vacancy): void
    {
        // Ensure status is consistent with submissions count
        $this->syncVacancyStatus($vacancy);
    }

    /**
     * Sync vacancy status based on submissions count
     */
    private function syncVacancyStatus(Vacancy $vacancy): void
    {
        $submissionsCount = $vacancy->submissions()->count();
        $currentStatus = $vacancy->status;

        if (!$currentStatus) return;

        $newStatusId = null;

        switch ($currentStatus->status_name) {
            case 'Open':
                // Jika ada submissions, ubah ke On-Process
                if ($submissionsCount > 0) {
                    $onProcessStatus = VacancyStatus::where('status_name', 'On-Process')->first();
                    $newStatusId = $onProcessStatus->id ?? null;
                }
                break;

            case 'On-Process':
                // Jika tidak ada submissions, kembali ke Open
                if ($submissionsCount === 0) {
                    $openStatus = VacancyStatus::where('status_name', 'Open')->first();
                    $newStatusId = $openStatus->id ?? null;
                }
                break;

            case 'Closed':
                // Status Closed tetap, tidak otomatis berubah
                break;
        }

        // Update status jika diperlukan dan berbeda dari current
        if ($newStatusId && $newStatusId !== $vacancy->status_id) {
            $vacancy->updateQuietly(['status_id' => $newStatusId]);
        }
    }
}