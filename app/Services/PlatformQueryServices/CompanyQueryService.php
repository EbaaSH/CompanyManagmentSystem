<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Company\Company;

class CompanyQueryService
{
    public function getCompanyById($companyId)
    {
        $user = auth()->user();
        $company = Company::query()
            ->forUserViaPermission($user)
            ->with([
                'manager',
                'branches',
                'employeeProfiles',
                'driverProfiles',
                'orders',
            ])
            ->where('id', $companyId)
            ->first();
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }
        return [
            'data' => $company,
            'message' => 'Company retrieved successfully',
            'code' => 200,
        ];
    }

    public function getAllCompanies()
    {
        $user = auth()->user();

        $companies = Company::query()
            ->forUserViaPermission($user)
            ->with([
                'manager',
                'branches',
                'employeeProfiles',
                'driverProfiles',
                'orders',
            ])
            ->paginate(10);

        return [
            'data' => $companies,
            'message' => 'Companies retrieved successfully',
            'code' => 200,
        ];
    }

}