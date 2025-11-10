<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Candidate;
use App\Observers\CandidateObserver;
use App\Models\Vacancy;
use App\Observers\VacancyObserver;
use App\Models\VacancySubmission;
use App\Observers\VacancySubmissionObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Candidate::observe(CandidateObserver::class);
        Vacancy::observe(VacancyObserver::class);
        VacancySubmission::observe(VacancySubmissionObserver::class);
        
        // Register custom middleware
        \Illuminate\Support\Facades\Route::aliasMiddleware('update.lastlogin', \App\Http\Middleware\UpdateLastLogin::class);
    }
}