<?php

namespace App\Providers;

use App\Models\Customer\CustomerProfile;
use App\Models\Menu\Menu;
use App\Policies\CustomerPolicy;
use App\Policies\MenuPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Menu::class, MenuPolicy::class);
        Gate::policy(CustomerProfile::class, CustomerPolicy::class);
    }
}
