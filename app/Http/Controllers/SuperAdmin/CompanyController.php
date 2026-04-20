<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyRequest;
use App\Http\Responses\Response;
use App\Models\Company\Company;
use App\Services\SuperAdmin\CompanyService;
use Illuminate\Support\Facades\DB;
use Throwable;

class CompanyController extends Controller
{
    protected $service;

    public function __construct(CompanyService $service)
    {
        $this->service = $service;
    }

    public function store(StoreCompanyRequest $request)
    {
        $this->authorize('createViaPermission', Company::class);
        DB::beginTransaction();
        try {
            $data = $this->service->createCompanyWithManager($request);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function update(UpdateCompanyRequest $request, $id)
    {
        $this->authorize('updateViaPermission', Company::class);
        DB::beginTransaction();

        try {
            $data = $this->service->updateCompany($request, $id);

            if ($data['code'] !== 200) {
                DB::rollBack();

                return Response::Error($data['data'], $data['message'], $data['code']);
            }

            DB::commit();

            return Response::Success($data['data'], $data['message']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}
