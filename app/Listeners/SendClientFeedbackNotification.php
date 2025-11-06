<?php

namespace App\Listeners;

use App\Events\ClientFeedbackGiven;
use App\Models\User;
use App\Notifications\ClientFeedbackNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendClientFeedbackNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ClientFeedbackGiven $event): void
    {
        $submission = $event->submission;
        
        // LOG: Debug info
        \Log::info('SendClientFeedbackNotification triggered', [
            'submission_id' => $submission->id,
            'submitted_by_user_id' => $submission->submitted_by_user_id,
            'candidate_id' => $submission->candidate_id
        ]);

        // 1. PRIORITAS: Kirim ke admin yang mengajukan kandidat ini
        if ($submission->submitted_by_user_id) {
            $submittingAdmin = User::where('id', $submission->submitted_by_user_id)
                ->whereHas('role', function ($query) {
                    $query->where('role_name', 'admin');
                })
                ->first();

            if ($submittingAdmin) {
                \Log::info('Sending notification to submitting admin', [
                    'admin_id' => $submittingAdmin->id,
                    'admin_email' => $submittingAdmin->email
                ]);
                
                $submittingAdmin->notify(new ClientFeedbackNotification($submission));
                return; // STOP - hanya kirim ke admin yang relevan
            }
        }

        // 2. FALLBACK: Jika tidak ada admin specific, kirim ke SEMUA admin
        $allAdmins = User::whereHas('role', function ($query) {
            $query->where('role_name', 'admin');
        })->get();

        \Log::info('No specific admin found, sending to all admins', [
            'admin_count' => $allAdmins->count(),
            'admin_emails' => $allAdmins->pluck('email')
        ]);

        Notification::send($allAdmins, new ClientFeedbackNotification($submission));
    }
}