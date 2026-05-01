<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Services\AI\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AIRecommendationController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendationService
    ) {}

    public function customer(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                // 'customer_id' => ['required', 'integer'],
                'top_k' => ['nullable', 'integer', 'min:1', 'max:20'],
            ]);

            $result = $this->recommendationService->recommendForCustomer(
                topK: $data['top_k'] ?? 5
            );
            if ($result['code'] !== 200) {
                return Response::Error($result['data'], $result['message'], $result['code']);
            }

            return Response::Success($result['data'], $result['message'], $result['code']);

        } catch (Throwable $th) {
            Log::error('AI cart recommendation exception', [
                'message' => $th->getMessage(),
            ]);

            return Response::Error(null, $th->getMessage());
        }
    }

    public function cart(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'menu_item_ids' => ['required', 'array', 'min:1'],
                'menu_item_ids.*' => ['required', 'integer'],
                'top_k' => ['nullable', 'integer', 'min:1', 'max:20'],
            ]);

            $result = $this->recommendationService->recommendForCart(
                menuItemIds: $data['menu_item_ids'],
                topK: $data['top_k'] ?? 5
            );

            if ($result['code'] !== 200) {
                return Response::Error($result['data'], $result['message'], $result['code']);
            }

            return Response::Success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            Log::error('AI cart recommendation exception', [
                'message' => $th->getMessage(),
            ]);

            return Response::Error(null, $th->getMessage());
        }
    }
}
