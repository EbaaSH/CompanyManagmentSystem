<?php

namespace App\Models\Customer;

use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'loyalty_points', 'is_active'];

    // ─── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * Uses JOIN instead of whereHas for company/branch scopes
     * to avoid correlated subqueries on large orders tables.
     *
     * super-admin     → all customers
     * company-manager → customers who ordered from their company
     * branch-manager  → customers who ordered from their branch
     * employee        → customers who ordered from their branch
     * driver          → no access
     * customer        → own profile only
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,

            $user->hasRole('company-manager') => $query
                ->join('orders', 'orders.customer_id', '=', 'customer_profiles.id')
                ->where('orders.company_id', $user->ownedCompany->id)
                ->select('customer_profiles.*')
                ->distinct(),

            $user->hasRole('branch-manager') => $query
                ->join('orders', 'orders.customer_id', '=', 'customer_profiles.id')
                ->where('orders.branch_id', $user->ownedBranch->id)
                ->select('customer_profiles.*')
                ->distinct(),

            $user->hasRole('employee') => $query
                ->join('orders', 'orders.customer_id', '=', 'customer_profiles.id')
                ->where('orders.branch_id', $user->employeeProfile->branch_id)
                ->select('customer_profiles.*')
                ->distinct(),

            $user->hasRole('customer') => $query->where('user_id', $user->id),

            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('customers.scope.all') => $query,

            $user->can('customers.scope.company') => $query
                ->join('orders', 'orders.customer_id', '=', 'customer_profiles.id')
                ->where('orders.company_id', $user->resolveCompanyId())
                ->select('customer_profiles.*')
                ->distinct(),

            $user->can('customers.scope.branch') => $query
                ->join('orders', 'orders.customer_id', '=', 'customer_profiles.id')
                ->where('orders.branch_id', $user->resolveBranchId())
                ->select('customer_profiles.*')
                ->distinct(),

            $user->can('customers.scope.own') => $query->where('user_id', $user->id),

            default => $query->whereRaw('0 = 1'),
        };
    }
}
