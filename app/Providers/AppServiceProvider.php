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
        $this->ensureDirectoriesExist();
    }

    private function ensureDirectoriesExist(): void
    {
        $directories = [
            storage_path('app/private/docking'),
            storage_path('app/private/docking/generated'),
        ];

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
}
