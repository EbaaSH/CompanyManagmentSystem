<?php

namespace App\Providers;

use App\Events;
use App\Listeners;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application
     */
    protected $listen = [
        // Order Events
        Events\OrderPlaced::class => [
            Listeners\HandleOrderPlaced::class,
        ],
        Events\OrderConfirmed::class => [
            Listeners\HandleOrderConfirmed::class,
        ],
        Events\OrderPreparing::class => [
            Listeners\HandleOrderPreparing::class,
        ],
        Events\OrderReady::class => [
            Listeners\HandleOrderReady::class,
        ],
        Events\OrderPickedUp::class => [
            Listeners\HandleOrderPickedUp::class,
        ],
        Events\OrderDelivered::class => [
            Listeners\HandleOrderDelivered::class,
        ],
        Events\OrderCancelled::class => [
            Listeners\HandleOrderCancelled::class,
        ],
        Events\OrderRejected::class => [
            Listeners\HandleOrderRejected::class,
        ],

        // Delivery Events
        Events\DriverAssigned::class => [
            Listeners\HandleDriverAssigned::class,
        ],
        Events\DeliveryAccepted::class => [
            // Start tracking
        ],
        Events\DeliveryFailed::class => [
            Listeners\HandleDeliveryFailed::class,
        ],

        // Payment Events
        Events\PaymentProcessed::class => [
            // Process payment
        ],
    ];

    /**
     * Register any events for your application
     */
    public function boot()
    {
        // parent::boot();

        // You can also register event listeners using closures here
        // Event::listen(OrderPlaced::class, function (OrderPlaced $event) {
        //     // Handle the event
        // });
    }

    /**
     * Determine if events and listeners should be automatically discovered
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
