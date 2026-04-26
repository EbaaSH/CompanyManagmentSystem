<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Responses\Response;
use App\Services\Auth\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProfileController extends Controller
{
    protected $profileService;
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }
    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $this->profileService->updateProfile($request);

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
    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $this->profileService->updatePassword($request);

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
