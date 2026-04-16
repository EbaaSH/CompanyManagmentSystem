<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyCreateRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Http\Responses\Response;
use App\Services\Company\CompanyService;
use Illuminate\Support\Facades\DB;
use Throwable;

class CompanyController extends Controller
{
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Get all companies.
     */
    public function index()
    {
        try {
            $data = $this->companyService->getAllCompanies();
            if ($data['code'] == 200) {

                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Create a new company.
     */
    public function store(CompanyCreateRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->companyService->createCompany($request);
            if ($data['code'] == 201) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }
            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Get details of a single company.
     */
    public function show($companyId)
    {
        DB::beginTransaction();
        try {
            $data = $this->companyService->getCompany($companyId);

            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }
            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Update an existing company.
     */
    public function update(CompanyUpdateRequest $request, $companyId)
    {
        DB::beginTransaction();
        try {
            $data = $this->companyService->updateCompany($request, $companyId);

            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}
