<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Requests\Customer\UpdatePasswordCustomerRequest;
use App\Http\Responses\Response;
use App\Models\Customer\CustomerProfile;
use App\Services\Customer\CustomerService;
use Illuminate\Support\Facades\DB;
use Throwable;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function registerCustomer(StoreCustomerRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->customerService->customerRegister($request);

            if (!$data['code'] == 201) {
                DB::rollBack();
                return Response::Error($data['data'], $data['message'], $data['code']);
            }

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function updateCustomer(UpdateCustomerRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $customer = CustomerProfile::query()
                ->forUserViaPermission($user)
                ->find($id);
            if (!$customer) {
                return Response::Error(null, 'customer not found', 404);
            }
            $this->authorize('update', $customer);
            DB::beginTransaction();
            $data = $this->customerService->updateCustomer($request, $id);
            if ($data['code'] !== 200) {
                DB::rollBack();
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }


}
