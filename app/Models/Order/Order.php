<?php

namespace App\Models\Order;

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Customer\CustomerAddress;
use App\Models\Customer\CustomerProfile;
use App\Models\Delivery\Delivery;
use App\Models\Driver\DriverProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_number', 'customer_id', 'company_id', 'branch_id', 'delivery_address_id', 'driver_id', 'status', 'notes'];

    // ─── Relationships ────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function orderInvoice(): HasOne
    {
        return $this->hasOne(OrderInvoice::class);
    }
    public function orderStatus(): HasOne
    {
        return $this->hasOne(OrderStatus::class);
    }
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * super-admin     → all orders (write blocked via Policy)
     * company-manager → orders in their company
     * branch-manager  → orders in their branch
     * employee        → orders in their branch
     * driver          → only orders assigned to them (read-only via Policy)
     * customer        → own orders only
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,
            $user->hasRole('company-manager') => $query->where('company_id', $user->ownedCompany->id),
            $user->hasRole('branch-manager') => $query->where('branch_id', $user->ownedBranch->id),
            $user->hasRole('employee') => $query->where('branch_id', $user->employeeProfile->branch_id),
            $user->hasRole('driver') => $query->where('driver_id', $user->driverProfile->id),
            $user->hasRole('customer') => $query->where('customer_id', $user->customerProfile->id),
            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('orders.scope.all') => $query,
            $user->can('orders.scope.company') => $query->where('company_id', $user->resolveCompanyId()),
            $user->can('orders.scope.branch') => $query->where('branch_id', $user->resolveBranchId()),
            $user->can('orders.scope.assigned') => $query->where('driver_id', $user->driverProfile->id),
            $user->can('orders.scope.own') => $query->where('customer_id', $user->customerProfile->id),
            default => $query->whereRaw('0 = 1'),
        };
    }
}
