<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'type', 'title', 'message', 'is_read', 'read_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {

            // 1. SUPER ADMIN → everything
            if ($user->can('notification.scope.all')) {
                return;
            }

            // 2. COMPANY MANAGER → all users in same company
            if ($user->can('notification.scope.company')) {
                $q->orWhereHas('user', function ($u) use ($user) {
                    $u->whereHas('ownedCompany', function ($c) use ($user) {
                        $c->where('id', $user->resolveCompanyId());
                    });
                });
            }

            // 3. BRANCH MANAGER → all users in same branch
            if ($user->can('notification.scope.branch')) {
                $q->orWhereHas('user', function ($u) use ($user) {
                    $u->whereHas('ownedBranch', function ($b) use ($user) {
                        $b->where('id', $user->resolveBranchId());
                    });
                });
            }

            // 4. EMPLOYEE / DRIVER / CUSTOMER → only own notifications
            if (
                $user->can('notifications.scope.assigned') ||
                $user->can('notification.scope.own')
            ) {
                $q->orWhere('user_id', $user->id);
            }
        });
    }
}
