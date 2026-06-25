<?php

namespace App\Providers;

use App\Policies\SubscriptionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('pro-features', [SubscriptionPolicy::class, 'accessProFeatures']);

        Gate::define('max-features', [SubscriptionPolicy::class, 'accessMaxFeatures']);

        Gate::define('active-subscription', [SubscriptionPolicy::class, 'hasSubscription']);
    }
}
