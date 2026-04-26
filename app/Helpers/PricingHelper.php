<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class PricingHelper
{
    public static function calculate($subtotal, $branch, $customerLat, $customerLng)
    {
        // =========================
        // 1. TAX
        // =========================
        $taxRate = config('pricing.tax_rate');
        $tax = round($subtotal * $taxRate, 2);

        // =========================
        // 2. DISTANCE
        // =========================
        $distance = self::calculateDistance(
            $branch->latitude,
            $branch->longitude,
            $customerLat,
            $customerLng
        );

        // =========================
        // 3. DELIVERY FEE
        // =========================
        $deliveryFee = self::getDeliveryFee($distance);

        // =========================
        // 4. TOTAL
        // =========================
        $total = $subtotal + $tax + $deliveryFee;
        Log::info("Subtotal: $subtotal, Tax: $tax, Delivery Fee: $deliveryFee, Total: $total, Distance: $distance");
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'delivery_fee' => $deliveryFee,
            'total' => round($total, 2),
            'distance_km' => round($distance, 2),
        ];
    }

    // =========================
    // DELIVERY LOGIC
    // =========================
    private static function getDeliveryFee($distance)
    {
        $tiers = config('pricing.delivery_tiers');

        foreach ($tiers as $tier) {
            if ($tier['max_distance'] === null || $distance <= $tier['max_distance']) {
                return $tier['fee'];
            }
        }

        return 0;
    }

    // =========================
    // HAVERSINE DISTANCE
    // =========================
    private static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) *
            sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}