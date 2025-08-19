<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ArticleCategories;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Member check

        Gate::define('Member', function ($user)
        {
            return $user->has_user_role('Member');
        });

        
        // Admin check

        Gate::define('Admin', function ($user)
        {
            return $user->has_user_role('Admin');
        });

    }
}
