<?php

use App\Models\Delivery\Delivery;
use App\Models\Driver\DriverProfile;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('delivery.{deliveryId}', function ($user, $deliveryId) {
    $delivery = Delivery::with(['driver', 'order.customer'])->find($deliveryId);

    return $delivery
        && (
            $delivery->driver?->user_id === $user->id
            || $delivery->order?->customer?->user_id === $user->id
        );
});

Broadcast::channel('driver.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

Broadcast::channel('customer.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    return $user->can('orders.scope.branch')
        || $user->can('orders.scope.company')
        || $user->can('orders.scope.all');
});
Broadcast::channel('driver.location.{driverId}', function ($user, $driverId) {
    $driver = DriverProfile::find($driverId);

    return $driver && (
        $driver->user_id === $user->id
        || $user->can('orders.scope.branch')
        || $user->can('orders.scope.company')
        || $user->can('orders.scope.all')
    );
});

Broadcast::channel('driver.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
