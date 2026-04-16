<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Services\Auth\OTPService;
use Illuminate\Http\Request;
use Throwable;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function verify(Request $request)
    {
        $data = [];
        try {

            $data = $this->otpService->verify($request);
            if ($data['code'] == 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();

            return Response::Error($data, $message);
        }
    }

    public function resendCode()
    {
        $data = [];
        try {

            $data = $this->otpService->resendCode();
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
