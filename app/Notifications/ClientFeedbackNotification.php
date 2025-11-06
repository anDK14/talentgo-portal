<?php

namespace App\Notifications;

use App\Models\VacancySubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ClientFeedbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VacancySubmission $submission
    ) {
        Log::info('ClientFeedbackNotification created', [
            'submission_id' => $submission->id,
            'candidate' => $submission->candidate->unique_talent_id
        ]);
    }

    public function via(object $notifiable): array
    {
        Log::info('ClientFeedbackNotification via method called', [
            'notifiable' => $notifiable->email
        ]);
        
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $candidate = $this->submission->candidate;
        $vacancy = $this->submission->vacancy;
        $client = $vacancy->client;
        
        $status = $this->submission->status->status_name;
        $statusColor = match($status) {
            'client_interested' => '🟢',
            'client_rejected' => '🔴',
            default => '⚪'
        };

        $statusText = match($status) {
            'client_interested' => '**TERTARIK** - Silakan jadwalkan wawancara',
            'client_rejected' => '**TIDAK SESUAI** - Kandidat tidak sesuai kebutuhan',
            default => strtoupper($status)
        };

        // Build email - FIX: Simpan dalam variable $mail
        $mail = (new MailMessage)
            ->from('notifications@talentgo.com', 'TalentGO Portal')
            ->replyTo('hr@talentgo.com', 'TalentGO HR Team')
            ->subject("{$statusColor} Feedback Client - {$candidate->unique_talent_id} - {$client->company_name}")
            ->greeting('Halo Tim TalentGO!')
            ->line("**{$client->company_name}** telah memberikan feedback untuk kandidat:")
            ->line('') // Empty line for spacing
            ->line("**Kandidat:** {$candidate->unique_talent_id}")
            ->line("**Posisi:** {$vacancy->position_name} ({$vacancy->level})")
            ->line("**Status:** {$statusText}")
            ->line("**Client:** {$client->company_name}")
            ->line("**Contact:** {$client->contact_person} - {$client->contact_email}");

        // Tambahkan info admin yang submit jika ada
        if ($this->submission->submittedBy) {
            $mail->line("**Diajukan oleh:** {$this->submission->submittedBy->email}");
        }

        $mail->line('') // Empty line
            ->action('Lihat Detail di Admin Panel', url('/admin'))
            ->line('Silakan follow up sesuai dengan feedback client.');

        // FIX: Return $mail di akhir method
        return $mail;
    }

    public function toArray(object $notifiable): array
{
    $candidate = $this->submission->candidate;
    $vacancy = $this->submission->vacancy;
    $client = $vacancy->client;
    $status = $this->submission->status->status_name;

    // Text untuk notification
    $statusText = match($status) {
        'client_interested' => 'tertarik',
        'client_rejected' => 'menolak',
        default => 'memberikan feedback'
    };

    // Color untuk badge
    $statusColor = match($status) {
        'client_interested' => 'success',
        'client_rejected' => 'danger',
        default => 'info'
    };

    // Icon untuk notification
    $statusIcon = match($status) {
        'client_interested' => 'heroicon-o-check-circle',
        'client_rejected' => 'heroicon-o-x-circle',
        default => 'heroicon-o-bell'
    };

    return [
        // Data untuk database
        'submission_id' => $this->submission->id,
        'candidate_id' => $this->submission->candidate_id,
        'vacancy_id' => $this->submission->vacancy_id,
        'client_id' => $client->id,
        
        // Data untuk display notification
        'type' => 'client_feedback',
        'title' => 'Feedback Client',
        'message' => "{$client->company_name} {$statusText} pada kandidat {$candidate->unique_talent_id}",
        'body' => "Posisi: {$vacancy->position_name} | Status: " . strtoupper($status),
        
        // Styling untuk Filament
        'icon' => $statusIcon,
        'color' => $statusColor,
        'url' => route('filament.admin.resources.vacancy-submissions.edit', $this->submission),
        
        // Metadata tambahan
        'candidate_unique_id' => $candidate->unique_talent_id,
        'vacancy_position' => $vacancy->position_name,
        'client_company' => $client->company_name,
        'feedback_status' => $status,
        'submitted_at' => $this->submission->created_at->toISOString(),
    ];
}
}