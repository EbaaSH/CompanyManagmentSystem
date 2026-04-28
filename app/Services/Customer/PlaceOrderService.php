<?php

namespace App\Services\Customer;

use App\Action\Orders\ConfirmOrder;
use App\Events\OrderPlaced;
use App\Helpers\PricingHelper;
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
    private $confirmAction;

    public function __construct(ConfirmOrder $confirmAction) {}

    public function confirmActionFun($order)
    {
        return new ConfirmOrder($order);
    }

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

        // Process items and calculate totals
        $subtotal = 0;
        foreach ($request->items as $itemData) {
            $subtotal += $this->createOrderItem($order, $itemData);
        }

        // Create invoice
        $customer = auth()->user()->customerProfile;
        $address = $customer->addresses()->find($request->delivery_address_id);

        $invoiceanddistance = $this->createInvoice($order, $subtotal, $address->latitude, $address->longitude);
        $invoice = $invoiceanddistance['invoice'];
        $distance_km = $invoiceanddistance['distance_km'];

        if ($distance_km > 150) {
            throw ValidationException::withMessages([
                'delivery' => 'Delivery not available for this distance',
            ]);
        }

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
        $this->confirmAction = $this->confirmActionFun($order);

        $this->confirmAction->autoConfirm();

        if ($order->status != 'confirmed') {
            event(new OrderPlaced($order));
        }

        // Return response with estimated time
        return [
            'data' => $this->formatOrderResponse($order, $distance_km),
            'message' => 'Order placed and confirmed successfully',
            'code' => 201,
        ];

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
                $option = ItemOption::with('optionGroup')->where('is_available', true)
                    ->find($optionId['option_id']);
                // dd($option);
                if (! $option) {
                    throw ValidationException::withMessages([
                        'options' => 'Invalid option selected',
                    ]);
                }

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
    private function createInvoice(Order $order, float $subtotal, $customerLat, $customerLng)
    {
        $branch = $order->branch;

        $result = PricingHelper::calculate(
            $subtotal,
            $branch,
            $customerLat,
            $customerLng
        );

        $total = $result['total'];

        $invoice = OrderInvoice::create([
            'order_id' => $order->id,
            'subtotal' => $subtotal,
            'delivery_free' => $result['delivery_fee'] ?? 5.00,
            'tax' => $result['tax'],
            'discount' => 0,
            'total' => $total,
        ]);

        return [
            'invoice' => $invoice,
            'distance_km' => $result['distance_km'],
        ];
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
        if (! $this->isBranchOpen($branch)) {
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
        // $address = $order->deliveryAddress;
        // if (! $address || ! $address->is_default) {
        //     // Additional validation if needed
        // }

        // All validations passed

    }

    /**
     * CHECK IF BRANCH IS OPEN
     */
    /**
     * CHECK IF BRANCH IS OPEN TODAY
     * Simple check: has opening hours for today and current time is within range
     */
    private function isBranchOpen($branch): bool
    {
        try {
            $todayDayName = now()->format('l'); // e.g. Monday
            $currentTime = now()->format('H:i:s');

            $hours = $branch->branchTimeHistories()
                ->whereHas('weekDays', function ($q) use ($todayDayName) {
                    $q->where('day_name', $todayDayName);
                })
                ->first();

            if (! $hours) {
                return false;
            }

            return $currentTime >= $hours->opening_time
                && $currentTime <= $hours->closing_time;

        } catch (\Exception $e) {
            return false;
        }
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
     * Uses already-loaded relationships to avoid extra queries
     */
    private function formatOrderResponse(Order $order, $distance_km): array
    {
        // Calculate estimated preparation time from order items
        $estimatedPrepTime = 0;
        if ($order->orderItems && count($order->orderItems) > 0) {
            $estimatedPrepTime = $order->orderItems
                ->pluck('menuItem.preparation_time_minutes')
                ->filter()
                ->max() ?? 0;
        }
        // estimated time + 2 minute for every KM
        $estimatedDeliveryTime = ($estimatedPrepTime ?? 0) + $distance_km * 2;

        // Format items
        $items = [];
        if ($order->orderItems) {
            foreach ($order->orderItems as $item) {
                $options = [];
                if ($item->orderItemOptions) {
                    $options = $item->orderItemOptions
                        ->pluck('option_name_snapshot')
                        ->toArray();
                }

                $items[] = [
                    'name' => $item->item_name_snapshot,
                    'price' => $item->item_price_snapshot,
                    'quantity' => $item->quantity,
                    'options' => $options,
                ];
            }
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer' => [
                'name' => $order->customer?->user?->name ?? 'Guest',
                'phone' => $order->customer?->user?->phone ?? 'N/A',
            ],
            'branch' => [
                'id' => $order->branch->id,
                'name' => $order->branch->name,
                'address' => $order->branch->address,
            ],
            'items' => $items,
            'invoice' => [
                'subtotal' => $order->orderInvoice?->subtotal ?? 0,
                'tax' => $order->orderInvoice?->tax ?? 0,
                'delivery_free' => $order->orderInvoice?->delivery_free ?? 0,
                'total' => $order->orderInvoice?->total ?? 0,
            ],
            'estimated_time' => "{$estimatedDeliveryTime} minutes",
            'delivery_address' => $order->deliveryAddress?->address ?? 'N/A',
            'created_at' => $order->created_at,
        ];
    }

    /**
     * UPDATE ORDER
     * Only allowed in PENDING state
     */
    public function updateOrder($request, $id)
    {
        $user = auth()->user();
        $order = Order::query()
            ->forUserViaPermission($user)
            ->find($id);

        if (! $order) {
            return [
                'data' => null,
                'message' => 'order not found',
                'code' => 404,
            ];
        }

        if (! in_array($order->status, ['pending', 'confirmed'])) {
            return [
                'data' => null,
                'message' => 'Orders can only be updated brafore preparing status',
                'code' => 400,
            ];
        }

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
        $customer = auth()->user()->customerProfile;
        $address = $customer->addresses()->find($request->delivery_address_id);
        $result = PricingHelper::calculate(
            $subtotal,
            $order->branch,
            $address->latitude,
            $address->longitude
        );
        if ($result['distance_km'] > 150) {
            throw ValidationException::withMessages([
                'delivery' => 'Delivery not available for this distance',
            ]);
        }
        $order->orderInvoice()->update([
            'subtotal' => $subtotal,
            'total' => $subtotal + $result['delivery_fee'] + $result['tax'],
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
            'data' => $this->formatOrderResponse($order, $result['distance_km']),
            'message' => 'Order updated successfully',
            'code' => 200,
        ];

    }
}
