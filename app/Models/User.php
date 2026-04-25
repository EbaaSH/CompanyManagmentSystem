<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Customer\CustomerProfile;
use App\Models\Driver\DriverProfile;
use App\Models\Employee\EmployeeProfile;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'phone_verified_at'])]
#[Hidden(['password', 'remember_token',])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    // The company this user owns (company-manager anchor)
    public function ownedCompany(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    // The branch this user owns (branch-manager anchor)
    public function ownedBranch(): HasOne
    {
        return $this->hasOne(Branch::class, 'user_id');
    }

    // Regular employee profile
    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    // Driver profile
    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    // Customer profile
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    // Convenience: resolve branch_id regardless of role
    // Used internally by scope helpers
    public function resolveBranchId(): ?int
    {
        return $this->ownedBranch?->id
            ?? $this->employeeProfile?->branch_id
            ?? $this->driverProfile?->branch_id;
    }

    // Convenience: resolve company_id regardless of role
    public function resolveCompanyId(): ?int
    {
        return $this->ownedCompany?->id
            ?? $this->employeeProfile?->company_id
            ?? $this->driverProfile?->company_id;
    }
}
