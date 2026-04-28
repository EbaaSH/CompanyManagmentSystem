<?php

namespace App\Providers;

use App\Models\Customer\CustomerProfile;
use App\Models\Delivery\Delivery;
use App\Models\Menu\Menu;
use App\Models\Order\Order;
use App\Policies\CustomerPolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\MenuPolicy;
use App\Policies\OrderPolicy;
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
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Delivery::class, DeliveryPolicy::class);
    }
}
