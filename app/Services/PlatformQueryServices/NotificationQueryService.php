<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Notification;

class NotificationQueryService
{
    public function getNotifyById($notifyId)
    {
        $user = auth()->user();
        $notify = Notification::query()
            ->forUserViaPermission($user)
            ->with(
                'user'
            )
            ->find($notifyId);
        if (!$notify) {
            return [
                'data' => null,
                'message' => 'notify not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $notify,
            'message' => 'notify found',
            'code' => 200,
        ];
    }

    public function getAllNotifications()
    {
        $user = auth()->user();
        $notifications = Notification::query()
            ->forUserViaPermission($user)
            ->with(
                'user'
            )
            ->paginate(10);
        return [
            'data' => $notifications,
            'message' => 'notifications found',
            'code' => 200,
        ];
    }
}