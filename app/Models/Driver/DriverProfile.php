<?php

namespace App\Models\Driver;

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Delivery\Delivery;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'company_id', 'branch_id', 'vehicle_type', 'plate_number', 'availability_status', 'current_latitude', 'current_longitude', 'is_active'];

    // ─── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'driver_id');
    }

    public function setAvailability($available)
    {
        $this->availability_status = $available;
        $this->save();
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * super-admin     → all drivers
     * company-manager → drivers in their company
     * branch-manager  → drivers in their branch
     * employee        → drivers in their branch (they coordinate)
     * driver          → own profile only
     * customer        → no access
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,
            $user->hasRole('company-manager') => $query->where('company_id', $user->ownedCompany->id),
            $user->hasRole('branch-manager') => $query->where('branch_id', $user->ownedBranch->id),
            $user->hasRole('employee') => $query->where('branch_id', $user->employeeProfile->branch_id),
            $user->hasRole('driver') => $query->where('user_id', $user->id),
            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('drivers.scope.all') => $query,
            $user->can('drivers.scope.company') => $query->where('company_id', $user->resolveCompanyId()),
            $user->can('drivers.scope.branch') => $query->where('branch_id', $user->resolveBranchId()),
            $user->can('drivers.scope.own') => $query->where('user_id', $user->id),
            default => $query->whereRaw('0 = 1'),
        };
    }
}
