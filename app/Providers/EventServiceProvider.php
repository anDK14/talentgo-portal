<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\ClientFeedbackGiven;
use App\Listeners\SendClientFeedbackNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ClientFeedbackGiven::class => [
            SendClientFeedbackNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}