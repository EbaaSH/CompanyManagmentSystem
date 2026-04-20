<?php

namespace App\Models\Company;

use App\Models\Driver\DriverProfile;
use App\Models\Employee\EmployeeProfile;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'legal_name', 'email', 'phone', 'status', 'user_id'];


    // ─── Relationships ────────────────────────────────────────────────

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }


    public function employeeProfiles(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function driverProfiles(): HasMany
    {
        return $this->hasMany(DriverProfile::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names (hardcoded)
     *
     * super-admin     → all companies
     * company-manager → only the company they own
     * customer        → all active companies (browsing)
     * others          → no access
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,
            $user->hasRole('company-manager') => $query->where('user_id', $user->id),
            $user->hasRole('customer') => $query->where('status', 'active'),
            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions (scalable)
     *
     * Same logic but driven by permission strings.
     * Adding new roles only requires updating the seeder.
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('companies.scope.all') => $query,
            $user->can('companies.scope.own') => $query->where('user_id', $user->id),
            $user->can('companies.scope.active') => $query->where('status', 'active'),
            default => $query->whereRaw('0 = 1'),
        };
    }
}
