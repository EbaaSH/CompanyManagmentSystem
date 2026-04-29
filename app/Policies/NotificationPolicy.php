<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user)
    {
        return $user->can('notification.scope.all')
            || $user->can('notification.scope.company')
            || $user->can('notification.scope.branch')
            || $user->can('notifications.scope.assigned')
            || $user->can('notification.scope.own');
    }

    public function view(User $user, Notification $notification): bool
    {
        return match (true) {
            $user->can('notification.scope.all') => true,

            $user->can('notification.scope.company') => $notification->user?->company_id === $user->resolveCompanyId(),

            $user->can('notification.scope.branch') => $notification->user?->branch_id === $user->resolveBranchId(),

            $user->can('notifications.scope.assigned') => $notification->user_id === $user->id,

            $user->can('notification.scope.own') => $notification->user_id === $user->id,

            default => false,
        };
    }
}
