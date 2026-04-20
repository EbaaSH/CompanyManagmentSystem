<?php

namespace App\Models\Menu;

use App\Models\Company\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'description', 'is_active', 'start_time', 'end_time'];

    // ─── Relationships ────────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function categories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * super-admin     → all menus
     * company-manager → menus for branches in their company
     * branch-manager  → menus for their branch
     * employee        → menus for their branch
     * driver          → no access
     * customer        → active menus open right now (is_active + time window)
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,

            $user->hasRole('company-manager') => $query->whereHas(
                'branch',
                fn($q) => $q->where('company_id', $user->ownedCompany->id)
            ),

            $user->hasRole('branch-manager') => $query->where(
                'branch_id',
                $user->ownedBranch->id
            ),

            $user->hasRole('employee') => $query->where(
                'branch_id',
                $user->employeeProfile->branch_id
            ),

            $user->hasRole('customer') => $query
                ->where('is_active', 1)
                ->whereTime('start_time', '<=', now())
                ->whereTime('end_time', '>=', now()),

            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('menus.scope.all') => $query,

            $user->can('menus.scope.company') => $query->whereHas(
                'branch',
                fn($q) => $q->where('company_id', $user->resolveCompanyId())
            ),

            $user->can('menus.scope.branch') => $query->where(
                'branch_id',
                $user->resolveBranchId()
            ),

            $user->can('menus.scope.active_now') => $query
                ->where('is_active', 1)
                ->whereTime('start_time', '<=', now())
                ->whereTime('end_time', '>=', now()),

            default => $query->whereRaw('0 = 1'),
        };
    }
}
