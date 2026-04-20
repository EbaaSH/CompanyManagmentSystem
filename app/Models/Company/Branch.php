<?php

namespace App\Models\Company;

use App\Models\Driver\DriverProfile;
use App\Models\Employee\EmployeeProfile;
use App\Models\Menu\Menu;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'code', 'address', 'city', 'latitude', 'longitude', 'phone', 'is_active'];


    public function branchTimeHistories()
    {
        return $this->hasMany(
            BranchTimeHistory::class,
            'branch_id',
        );
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(DriverProfile::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * super-admin     → all branches
     * company-manager → branches in their company
     * branch-manager  → only their owned branch
     * employee        → only their assigned branch
     * driver          → only their currently assigned branch
     * customer        → all active branches
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,
            $user->hasRole('company-manager') => $query->where('company_id', $user->ownedCompany->id),
            $user->hasRole('branch-manager') => $query->where('user_id', $user->id),
            $user->hasRole('employee') => $query->where('id', $user->employeeProfile->branch_id),
            $user->hasRole('driver') => $query->where('id', $user->driverProfile->branch_id),
            $user->hasRole('customer') => $query->where('is_active', 1),
            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('branches.scope.all') => $query,
            $user->can('branches.scope.company') => $query->where('company_id', $user->ownedCompany->id),
            $user->can('branches.scope.own') => $this->scopeOwnBranch($query, $user),
            $user->can('branches.scope.active') => $query->where('is_active', 1),
            default => $query->whereRaw('0 = 1'),
        };
    }

    private function scopeOwnBranch(Builder $query, User $user): Builder
    {
        // branch-manager → their owned branch
        if ($user->ownedBranch) {
            return $query->where('user_id', $user->id);
        }
        // driver → their currently assigned branch
        if ($user->driverProfile) {
            return $query->where('id', $user->driverProfile->branch_id);
        }
        // employee → their assigned branch
        if ($user->employeeProfile) {
            return $query->where('id', $user->employeeProfile->branch_id);
        }
        return $query->whereRaw('0 = 1');
    }

}
