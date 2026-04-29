<?php

namespace App\Http\Controllers\PaymentStripe;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Order\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Webhook;
class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return Response::Error(null, 'Invalid', 400);
        }

        switch ($event->type) {

            case 'payment_intent.succeeded':
                $this->success($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->failed($event->data->object);
                break;
        }

        return response('OK');
    }

    private function success($intent)
    {
        $orderId = $intent->metadata->order_id;

        $order = Order::find($orderId);
        if (!$order)
            return;

        $order->payment->update([
            'payment_status' => 'paid',
            // 'status' => 'accepted',
            'paid_at' => now()
        ]);

        Payment::where('payment_intent_id', $intent->id)
            ->update(['status' => 'completed']);
    }

    private function failed($intent)
    {
        Payment::where('payment_intent_id', $intent->id)
            ->update(['payment_status' => 'failed']);
    }
}
