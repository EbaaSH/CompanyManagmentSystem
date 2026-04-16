<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\Response;
use App\Services\Auth\AuthService;
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
        $data = [];
        try {

            $data = $this->authService->register($request);
            if ($data['code'] == 201) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function login(LoginRequest $request)
    {
        $data = [];
        try {

            $data = $this->authService->login($request);
            if ($data['code'] == 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function logout()
    {
        $data = [];
        try {

            $data = $this->authService->Logout();
            if ($data['code'] == 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function me()
    {
        $data = [];
        try {

            $data = $this->authService->me();
            if ($data['code'] == 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function refresh()
    {
        $data = [];
        try {

            $data = $this->authService->refresh();
            if ($data['code'] == 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }
}
