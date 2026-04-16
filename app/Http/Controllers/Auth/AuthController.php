<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\Response;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuthController extends Controller
{
    protected $authService;

    /**
     * Inject AuthService into the controller.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        $data = [];
        try {

            $data = $this->authService->register($request);
            if ($data['code'] == 201) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }
            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function login(LoginRequest $request)
    {
        $data = [];
        DB::beginTransaction();
        try {

            $data = $this->authService->login($request);
            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function logout()
    {
        DB::beginTransaction();
        $data = [];
        try {

            $data = $this->authService->Logout();
            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function me()
    {
        DB::beginTransaction();
        $data = [];
        try {

            $data = $this->authService->me();
            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function refresh()
    {
        DB::beginTransaction();
        $data = [];
        try {

            $data = $this->authService->refresh();
            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }
            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }
}
