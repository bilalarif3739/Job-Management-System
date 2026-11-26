<?php

namespace App\Providers;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Support\Presence\AlwaysConnectedTracker;
use App\Support\Presence\ConnectionTracker;
use App\Models\Channel;
use App\Models\Message;
use App\Models\PollModels\Poll;
use App\Models\Team;
use App\Policies\ChannelPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Policies\MessagePolicy;
use App\Policies\PollPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ConnectionTracker::class, AlwaysConnectedTracker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }
}
