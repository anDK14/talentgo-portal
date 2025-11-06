<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Candidate;
use App\Observers\CandidateObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Candidate::observe(CandidateObserver::class);
    }
}