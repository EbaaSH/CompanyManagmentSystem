<?php

namespace App\Http\Controllers\PaymentStripe;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Order\Order;
use App\Models\Payment;
use App\Services\StripePayment\StripeService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private $stripe;
    public function __construct(StripeService $stripe)
    {
        $this->stripe = $stripe;
    }
    public function create($orderId)
    {
        $user = auth()->user();
        $order = Order::query()
            ->forUserViaPermission($user)
            ->find($orderId);
        $payment = $order->payment;
        if ($payment->payment_status === 'paid') {
            return Response::Error(null, 'Already paid', 400);
        }
        if (!$payment) {
            return Response::Error(null, 'Payment not found', 404);
        }

        $intent = $this->stripe->createPaymentIntent($order);

        $payment->update([
            'gateway' => 'stripe',
            'payment_intent_id' => $intent->id,
        ]);

        return Response::Success([
            'client_secret' => $intent->client_secret
        ], 'payment intent created', 200);
    }
}
