<?php

namespace App\Services\StripePayment;

use App\Models\Payment;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPaymentIntent($order)
    {
        return PaymentIntent::create([
            'amount' => (int) ($order->orderInvoice->total * 100),
            'currency' => 'usd',
            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);
    }

    public function refund(Payment $payment, float $amount = null)
    {
        $refundData = [
            'payment_intent' => $payment->payment_intent_id,
        ];

        // Partial refund
        if ($amount) {
            $refundData['amount'] = (int) ($amount * 100);
        }

        $refund = \Stripe\Refund::create($refundData);

        return $refund;
    }
}