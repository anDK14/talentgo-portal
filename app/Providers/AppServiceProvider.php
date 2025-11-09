<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Candidate;
use App\Observers\CandidateObserver;
use App\Models\Vacancy;
use App\Observers\VacancyObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Candidate::observe(CandidateObserver::class);
        Vacancy::observe(VacancyObserver::class);
        
        // Register custom middleware
        \Illuminate\Support\Facades\Route::aliasMiddleware('update.lastlogin', \App\Http\Middleware\UpdateLastLogin::class);
    }
}