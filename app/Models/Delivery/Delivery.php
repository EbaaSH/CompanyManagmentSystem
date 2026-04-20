<?php

namespace App\Models\Delivery;

use App\Models\Driver\DriverProfile;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'driver_id', 'delivery_status', 'assigned_at', 'accepted_at', 'picked_up_at', 'delivered_at', 'proof_image_url', 'delivery_notes'];

    // ─── Relationships ────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }
    public function statusHistories(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * Uses JOIN for company/branch/customer scopes to avoid nested subqueries.
     *
     * super-admin     → all deliveries
     * company-manager → deliveries for orders in their company
     * branch-manager  → deliveries for orders in their branch
     * employee        → deliveries for orders in their branch
     * driver          → own deliveries only
     * customer        → deliveries for their own orders
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,

            $user->hasRole('company-manager') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.company_id', $user->ownedCompany->id)
                ->select('deliveries.*'),

            $user->hasRole('branch-manager') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.branch_id', $user->ownedBranch->id)
                ->select('deliveries.*'),

            $user->hasRole('employee') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.branch_id', $user->employeeProfile->branch_id)
                ->select('deliveries.*'),

            $user->hasRole('driver') => $query->where('driver_id', $user->driverProfile->id),

            $user->hasRole('customer') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.customer_id', $user->customerProfile->id)
                ->select('deliveries.*'),

            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('deliveries.scope.all') => $query,

            $user->can('deliveries.scope.company') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.company_id', $user->resolveCompanyId())
                ->select('deliveries.*'),

            $user->can('deliveries.scope.branch') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.branch_id', $user->resolveBranchId())
                ->select('deliveries.*'),

            $user->can('deliveries.scope.own') => $this->resolveOwnDelivery($query, $user),

            default => $query->whereRaw('0 = 1'),
        };
    }

    private function resolveOwnDelivery(Builder $query, User $user): Builder
    {
        // driver → own deliveries
        if ($user->driverProfile) {
            return $query->where('driver_id', $user->driverProfile->id);
        }
        // customer → deliveries for their orders
        return $query
            ->join('orders', 'orders.id', '=', 'deliveries.order_id')
            ->where('orders.customer_id', $user->customerProfile->id)
            ->select('deliveries.*');
    }


}
