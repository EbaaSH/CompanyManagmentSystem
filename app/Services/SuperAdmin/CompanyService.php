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
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole('company-manager');

        $company = Company::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status ?? "active",
        ]);

        $this->authorize('create', Company::class);

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
        $this->authorize('update', Company::class);
        $company = Company::find($companyId);
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }
        $company->manager()->update([
            'name' => $request->name ?? $company->manager->name,
            'email' => $request->email ?? $company->manager->email,
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
            'data' => $company,
            'message' => 'Company updated successfully',
            'code' => 200,
        ];
    }
}