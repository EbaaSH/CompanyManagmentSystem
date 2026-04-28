<?php

namespace App\Services\SuperAdmin;

use App\Models\Company\Company;
use App\Models\User;
use Propaganistas\LaravelPhone\PhoneNumber;

class CompanyService
{
    public function createCompanyWithManager($request)
    {
        $phone = new PhoneNumber($request->manager_phone);

        $normalized = $phone->formatE164();
        $user = User::create([
            'name' => $request->manager_name,
            'email' => $request->manager_email,
            'phone' => $normalized,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole('company-manager');
        $phone = new PhoneNumber($request->phone);

        $campanyNormalized = $phone->formatE164();
        $company = Company::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'email' => $request->email,
            'phone' => $campanyNormalized,
            'status' => $request->status ?? 'active',
        ]);

        // $user->update(['phone_verified_at' => now()]);

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
        if (! $company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }
        $phone = new PhoneNumber($request->manager_phone);

        $normalized = $phone->formatE164();
        $company->manager()->update([
            'name' => $request->manager_name ?? $company->manager->name,
            'email' => $request->manager_email ?? $company->manager->email,
            'phone' => $request->$normalized ?? $company->manager->phone,
            'password' => isset($request->password) ? bcrypt($request->password) : $company->manager->password,
        ]);
        $phone = new PhoneNumber($request->phone);

        $campanyNormalized = $phone->formatE164();
        $company->update([
            'name' => $request->name ?? $company->name,
            'legal_name' => $request->legal_name ?? $company->legal_name,
            'email' => $request->email ?? $company->email,
            'phone' => $campanyNormalized ?? $company->phone,
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

        if (! $company) {
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

        if (! $company) {
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
