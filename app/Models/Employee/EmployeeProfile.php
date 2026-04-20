<?php

namespace App\Models\Employee;

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'company_id', 'branch_id', 'job_title_id', 'hire_date', 'is_active'];

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
    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * super-admin     → all employees
     * company-manager → employees in their company
     * branch-manager  → employees in their branch
     * employee        → no access to employee listing
     * driver          → employees in their current branch (they work together)
     * customer        → no access
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,
            $user->hasRole('company-manager') => $query->where('company_id', $user->ownedCompany->id),
            $user->hasRole('branch-manager') => $query->where('branch_id', $user->ownedBranch->id),
            $user->hasRole('driver') => $query->where('branch_id', $user->driverProfile->branch_id),
            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('employees.scope.all') => $query,
            $user->can('employees.scope.company') => $query->where('company_id', $user->resolveCompanyId()),
            $user->can('employees.scope.branch') => $query->where('branch_id', $user->resolveBranchId()),
            default => $query->whereRaw('0 = 1'),
        };
    }
}
