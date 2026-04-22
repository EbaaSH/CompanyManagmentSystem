<?php

// app/Policies/OrderPolicy.php

namespace App\Policies;

use App\Models\Order\Order;
use App\Models\User;

class OrderPolicy
{
    // TECHNIQUE 1

    // public function view(User $user, Order $order): bool
    // {
    //     // The scope already filters — this is a safety net for direct route access
    //     return Order::forUser($user)->where('id', $order->id)->exists();
    // }

    // public function create(User $user): bool
    // {
    //     // Only customers place orders
    //     return $user->hasRole('customer');
    // }

    // public function update(User $user, Order $order): bool
    // {
    //     // driver → read-only
    //     // super-admin → read-only
    //     // customer → cannot update status
    //     return $user->hasAnyRole(['company-manager', 'branch-manager', 'employee']);
    // }

    // public function delete(User $user, Order $order): bool
    // {
    //     return $user->hasAnyRole(['super-admin', 'company-manager']);
    // }
    public function viewAny(User $user): bool
    {
        return $user->can('orders.scope.all')
            || $user->can('orders.scope.company')
            || $user->can('orders.scope.branch')
            || $user->can('orders.scope.assigned')
            || $user->can('orders.scope.own');
    }

    // TECHNIQUE 2
    public function view(User $user, Order $order): bool
    {
        return Order::forUserViaPermission($user)->where('id', $order->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('orders.write') && $user->can('orders.scope.own');
    }

    public function update(User $user, Order $order): bool
    {
        // must have write AND not be scoped to assigned-only (driver) or own (customer placing)
        return $user->can('orders.write')
            && !$user->can('orders.scope.assigned')
            && !$user->can('orders.scope.own');
    }
}
