<?php

namespace App\Services\Company;

use App\Models\Company\Company;

class CompanyService
{
    /**
     * Get all companies.
     */
    public function getAllCompanies()
    {
        $companies = Company::all();

        return [
            'data' => $companies,
            'message' => 'Companies retrieved successfully',
            'code' => 200,
        ];

    }

    /**
     * Create a new company.
     */
    public function createCompany($request)
    {
        $company = Company::create([
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        return [
            'data' => $company,
            'message' => 'Company created successfully',
            'code' => 201,
        ];

    }

    /**
     * Get a single company.
     */
    public function getCompany($companyId)
    {
        $company = Company::find($companyId);

        if (! $company) {
            return [
                'data' => $company,
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

    /**
     * Update a company.
     */
    public function updateCompany($request, $companyId)
    {
        $company = Company::find($companyId);

        if (! $company) {
            return [
                'data' => $company,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }

        $company->update([
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
        ]);

        return [
            'data' => $company,
            'message' => 'Company updated successfully',
            'code' => 200,
        ];
    }
}
