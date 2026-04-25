<?php

namespace App\Services\SuperAdmin;

use App\Models\Company\Company;
use App\Models\User;

class CompanyService
{
    public function createCompanyWithManager($request)
    {

        $user = User::create([
            'name' => $request->manager_name,
            'email' => $request->manager_email,
            'phone' => $request->manager_phone,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole('company-manager');

        $company = Company::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status ?? 'active',
        ]);

        $user->update(['phone_verified_at' => now()]);

        return [
            'data' => $company->load('manager'),
            'message' => 'Company created successfully',
            'code' => 201,
        ];
    }

    public function updateCompany($request, $companyId)
    {
        $user = auth()->user();
        $company = Company::find($companyId);
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }
        $company->manager()->update([
            'name' => $request->manager_name ?? $company->manager->name,
            'email' => $request->manager_email ?? $company->manager->email,
            'phone' => $request->manager_phone ?? $company->manager->phone,
            'password' => isset($request->password) ? bcrypt($request->password) : $company->manager->password,
        ]);
        $company->update([
            'name' => $request->name ?? $company->name,
            'legal_name' => $request->legal_name ?? $company->legal_name,
            'email' => $request->email ?? $company->email,
            'phone' => $request->phone ?? $company->phone,
            'status' => $request->status ?? $company->status,
        ]);

        return [
            'data' => $company->load('manager'),
            'message' => 'Company updated successfully',
            'code' => 200,
        ];
    }
    public function deleteCompany($companyId)
    {
        $company = Company::find($companyId);

        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }

        $company->delete();

        return [
            'data' => null,
            'message' => 'Company deleted successfully',
            'code' => 200,
        ];
    }
    public function restoreCompany($companyId)
    {
        $company = Company::onlyTrashed()->find($companyId);

        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }

        $company->restore();

        return [
            'data' => $company->load('manager'),
            'message' => 'Company restored successfully',
            'code' => 200,
        ];
    }
}
