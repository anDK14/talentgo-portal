<?php

namespace App\Events;

use App\Models\VacancySubmission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientFeedbackGiven
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VacancySubmission $submission
    ) {}
}