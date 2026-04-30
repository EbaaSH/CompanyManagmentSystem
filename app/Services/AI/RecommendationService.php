<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    private function baseUrl(): string
    {
        return rtrim(config('services.ai_recommendation.url'), '/');
    }

    public function recommendForCustomer(int $customerId, int $topK = 5): array
    {
        // $user = auth()->user();
        // $customerId = $user->customerProfile->id;
        $response = Http::timeout(10)->post($this->baseUrl().'/recommend/customer', [
            'customer_id' => $customerId,
            'top_k' => $topK,
        ]);

        if (! $response->successful()) {
            Log::error('AI customer recommendation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'data' => [],
                'message' => 'AI recommendation service failed.',
                'code' => 400,
            ];
        }

        return [
            'data' => $response->json(),
            'message' => 'Recommendations retrieved successfully.',
            'code' => 200,
        ];

    }

    public function recommendForCart(array $menuItemIds, int $topK = 5): array
    {

        $response = Http::timeout(10)->post($this->baseUrl().'/recommend/cart', [
            'menu_item_ids' => array_values($menuItemIds),
            'top_k' => $topK,
        ]);

        if (! $response->successful()) {
            Log::error('AI cart recommendation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'data' => [],
                'message' => 'AI cart recommendation service failed.',
                'code' => 400,
            ];
        }

        return [
            'data' => $response->json(),
            'message' => 'Cart recommendations retrieved successfully.',
            'code' => 200,
        ];

    }
}
