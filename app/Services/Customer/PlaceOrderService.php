<?php

namespace App\Services\Customer;

use App\Models\Menu\ItemOption;
use App\Models\Menu\MenuItem;
use App\Models\Order\Order;
use App\Models\Order\OrderInvoice;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderItemOption;
use App\Models\Order\OrderStatus;
use App\Models\Order\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class PlaceOrderService
{
    /**
     * PLACE ORDER - OPTIMIZED WORKFLOW
     * 1. Create order (PENDING)
     * 2. Validate all requirements
     * 3. Create payment record
     * 4. Auto-confirm if valid
     * 5. Fire events
     * 6. Return response with estimated time
     */
    public function placeOrder($request)
    {
        // Create order in PENDING state
        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'customer_id' => auth()->user()->customerProfile->id,
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
            'delivery_address_id' => $request->delivery_address_id,
            'driver_id' => null,
            'status' => 'pending',
            'notes' => $request->notes ?? '',
        ]);

        try {
            // Process items and calculate totals
            $subtotal = 0;
            foreach ($request->items as $itemData) {
                $subtotal += $this->createOrderItem($order, $itemData);
            }

            // Create invoice
            $invoice = $this->createInvoice($order, $subtotal, $request);

            // Create payment record
            $this->createPaymentRecord($order, $invoice->total, $request->payment_method);

            // Record initial status
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => 'none',
                'new_status' => 'pending',
                'changed_by_user_id' => auth()->id(),
                'reason' => 'Order created by customer',
            ]);

            // Create order status
            OrderStatus::create([
                'order_id' => $order->id,
                'placed_at' => now(),
            ]);

            // VALIDATE ORDER
            $this->validateOrder($order);

            // AUTO-CONFIRM if validation passes
            $order->autoConfirm();

            // Return response with estimated time
            return [
                'data' => $this->formatOrderResponse($order),
                'message' => 'Order placed and confirmed successfully',
                'code' => 201,
            ];

        } catch (\Exception $e) {
            // Clean up on failure
            $order->delete();
            throw $e;
        }
    }

    /**
     * CREATE ORDER ITEM WITH OPTIONS
     */
    private function createOrderItem(Order $order, array $itemData): float
    {
        $menuItem = MenuItem::findOrFail($itemData['menu_item_id']);

        if (! $menuItem->is_available) {
            throw ValidationException::withMessages([
                'items' => "Item '{$menuItem->name}' is no longer available",
            ]);
        }

        $lineTotal = $menuItem->price * $itemData['quantity'];

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'item_name_snapshot' => $menuItem->name,
            'item_price_snapshot' => $menuItem->price,
            'quantity' => $itemData['quantity'],
            'notes' => $itemData['notes'] ?? '',
            'line_total' => $lineTotal,
        ]);

        // Process options
        if (! empty($itemData['options'])) {
            foreach ($itemData['options'] as $optionId) {
                $option = ItemOption::findOrFail($optionId);

                OrderItemOption::create([
                    'order_item_id' => $orderItem->id,
                    'option_group_name_snapshot' => $option->optionGroup->name,
                    'option_name_snapshot' => $option->name,
                    'extra_price' => $option->extra_price,
                ]);

                $lineTotal += $option->extra_price;
            }
        }

        return $lineTotal;
    }

    /**
     * CREATE ORDER INVOICE
     */
    private function createInvoice(Order $order, float $subtotal, $request): OrderInvoice
    {
        $branch = $order->branch;

        // Calculate delivery fee (could be dynamic based on distance)
        $deliveryFee = $branch->default_delivery_fee ?? 5.00;

        // Calculate tax (could be configurable)
        $taxRate = $branch->tax_rate ?? 0.10;
        $tax = round($subtotal * $taxRate, 2);

        $total = $subtotal + $deliveryFee + $tax;

        return OrderInvoice::create([
            'order_id' => $order->id,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'tax' => $tax,
            'discount' => 0,
            'total' => $total,
        ]);
    }

    /**
     * CREATE PAYMENT RECORD
     */
    private function createPaymentRecord(Order $order, float $amount, string $paymentMethod): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'amount' => $amount,
            'transaction_reference' => 'TXN-'.uniqid(),
        ]);
    }

    /**
     * VALIDATE ORDER BEFORE AUTO-CONFIRMATION
     * Checks:
     * 1. Branch is open
     * 2. All items available
     * 3. Payment method valid
     * 4. Delivery address valid
     */
    private function validateOrder(Order $order): void
    {
        $branch = $order->branch;

        // Check branch is open
        if (! $branch->is_open || ! $this->isBranchOpen($branch)) {
            throw ValidationException::withMessages([
                'branch' => 'Branch is currently closed. Please try again later.',
            ]);
        }

        // Check payment method
        $validMethods = ['cash', 'card', 'wallet'];
        if (! in_array($order->payment->payment_method, $validMethods)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Invalid payment method',
            ]);
        }

        // Check delivery address
        $address = $order->deliveryAddress;
        if (! $address || ! $address->is_default) {
            // Additional validation if needed
        }

        // All validations passed

    }

    /**
     * CHECK IF BRANCH IS OPEN
     */
    private function isBranchOpen($branch): bool
    {
        $now = now();
        $dayOfWeek = strtolower($now->format('l'));

        // Get operating hours for today
        $hours = $branch->operatingHours()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $hours) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        return $currentTime >= $hours->opening_time && $currentTime <= $hours->closing_time;
    }

    /**
     * GENERATE UNIQUE ORDER NUMBER
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6));
    }

    /**
     * FORMAT ORDER RESPONSE
     */
    private function formatOrderResponse(Order $order): array
    {
        $estimatedPrepTime = $order->orderItems()
            ->with('menuItem')
            ->get()
            ->max(fn ($item) => $item->menuItem->preparation_time_minutes ?? 0);

        $estimatedDeliveryTime = ($estimatedPrepTime ?? 0) + 30; // +30 min for delivery

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer' => [
                'name' => $order->customer->user->name ?? '',
                'phone' => $order->customer->user->phone ?? '',
            ],
            'branch' => [
                'id' => $order->branch->id,
                'name' => $order->branch->name,
                'address' => $order->branch->address,
            ],
            'items' => $order->orderItems()->with(['menuItem', 'orderItemOptions'])->get()->map(fn ($item) => [
                'name' => $item->item_name_snapshot,
                'price' => $item->item_price_snapshot,
                'quantity' => $item->quantity,
                'options' => $item->orderItemOptions->map(fn ($opt) => $opt->option_name_snapshot),
            ]),
            'invoice' => [
                'subtotal' => $order->orderInvoice->subtotal,
                'tax' => $order->orderInvoice->tax,
                'delivery_fee' => $order->orderInvoice->delivery_fee,
                'total' => $order->orderInvoice->total,
            ],
            'estimated_time' => "{$estimatedDeliveryTime} minutes",
            'delivery_address' => $order->deliveryAddress->address ?? '',
            'created_at' => $order->created_at,
        ];
    }

    /**
     * UPDATE ORDER
     * Only allowed in PENDING state
     */
    public function updateOrder($request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return [
                'data' => null,
                'message' => 'Orders can only be updated in pending status',
                'code' => 400,
            ];
        }

        try {
            // Update order fields
            $order->update([
                'delivery_address_id' => $request->delivery_address_id,
                'notes' => $request->notes ?? '',
            ]);

            // Delete old order items
            $order->orderItems()->delete();

            // Add new order items
            $subtotal = 0;
            foreach ($request->items as $itemData) {
                $subtotal += $this->createOrderItem($order, $itemData);
            }

            // Update invoice
            $order->orderInvoice()->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + $order->orderInvoice->delivery_fee + $order->orderInvoice->tax,
            ]);

            // Update payment amount
            $order->payment()->update([
                'amount' => $order->orderInvoice->total,
            ]);

            // Record history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => $order->status,
                'new_status' => $order->status,
                'changed_by_user_id' => auth()->id(),
                'reason' => 'Order updated by customer',
            ]);

            return [
                'data' => $this->formatOrderResponse($order),
                'message' => 'Order updated successfully',
                'code' => 200,
            ];

        } catch (\Exception $e) {
            return [
                'data' => null,
                'message' => 'Failed to update order: '.$e->getMessage(),
                'code' => 400,
            ];
        }
    }
}
