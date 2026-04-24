<?php

namespace App\Services\Customer;

use App\Models\Order\Order;
use Illuminate\Auth\Access\AuthorizationException;

class OrderStateMachine
{
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Valid state transitions
     */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled', 'rejected'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready_for_pickup', 'cancelled'],
        'ready_for_pickup' => ['picked_up'],
        'picked_up' => ['delivered', 'failed_delivery'],
        'delivered' => [],
        'cancelled' => [],
        'rejected' => [],
        'failed_delivery' => ['ready_for_pickup'], // For retry
    ];

    /**
     * State permissions (who can trigger)
     */
    private const PERMISSIONS = [
        'pending' => ['system', 'branch-manager'],
        'confirmed' => ['system', 'employee', 'branch-manager'],
        'preparing' => ['employee', 'branch-manager'],
        'ready_for_pickup' => ['employee', 'branch-manager'],
        'picked_up' => ['driver', 'branch-manager'],
        'delivered' => ['driver', 'branch-manager'],
        'cancelled' => ['customer', 'employee', 'branch-manager'],
        'rejected' => ['employee', 'branch-manager'],
        'failed_delivery' => ['driver', 'branch-manager'],
    ];

    /**
     * Business rules for state transitions
     */
    private const RULES = [
        'pending_to_confirmed' => 'validateConfirmation',
        'confirmed_to_preparing' => 'validatePreparing',
        'preparing_to_ready_for_pickup' => 'validateReady',
        'ready_for_pickup_to_picked_up' => 'validatePickup',
        'picked_up_to_delivered' => 'validateDelivery',
        'picked_up_to_failed_delivery' => 'validateFailure',
        'any_to_cancelled' => 'validateCancellation',
        'any_to_rejected' => 'validateRejection',
    ];

    /**
     * Check if transition is valid
     */
    public function canTransition(string $newStatus, string $userRole = 'system'): bool
    {
        $currentStatus = $this->order->status;
        // dd($currentStatus);

        // Check if transition exists
        if (! isset(self::TRANSITIONS[$currentStatus])) {
            return false;
        }

        // Check if new status is allowed from current
        if (! in_array($newStatus, self::TRANSITIONS[$currentStatus])) {
            return false;
        }

        // Check if user role has permission for new status
        if (! in_array($userRole, self::PERMISSIONS[$newStatus] ?? [])) {
            return false;
        }

        return true;
    }

    /**
     * Perform transition with validation
     */
    public function transition(string $newStatus, string $userRole = 'system', ?string $reason = null)
    {
        if (! $this->canTransition($newStatus, $userRole)) {
            throw new AuthorizationException("Cannot transition from {$this->order->status} to {$newStatus}");
        }

        $transitionKey = "{$this->order->status}_to_{$newStatus}";
        $ruleMethod = self::RULES[$transitionKey] ?? null;

        // Validate business rules
        if ($ruleMethod && method_exists($this, $ruleMethod)) {
            $this->$ruleMethod();
        }

        // Perform transition
        $this->order->update([
            'status' => $newStatus,
        ]);

        // Record history
        $this->order->recordStatusHistory(
            $this->order->status,
            $newStatus,
            auth()->id() ?? 1,
            $reason
        );

        // Fire events
        event("order.{$newStatus}", $this->order);
    }

    /**
     * Business rule validations
     */
    private function validateConfirmation()
    {
        // Order items must be available
        foreach ($this->order->items as $item) {
            if (! $item->menuItem->is_available) {
                throw new \Exception("Item {$item->menuItem->name} is no longer available");
            }
        }

        // Branch must be open
        if (! $this->order->branch->isOpen()) {
            throw new \Exception('Branch is currently closed');
        }

        // Payment method must be valid
        $validMethods = ['cash', 'card', 'wallet'];
        if (! in_array($this->order->payment->payment_method, $validMethods)) {
            throw new \Exception('Invalid payment method');
        }
    }

    private function validatePreparing()
    {
        // Order must be confirmed
        if ($this->order->status !== 'confirmed') {
            throw new \Exception('Order must be confirmed before preparing');
        }
    }

    private function validateReady()
    {
        // Order must be preparing
        if ($this->order->status !== 'preparing') {
            throw new \Exception('Order must be in preparing state');
        }

        // Delivery must not exist yet
        if ($this->order->delivery && $this->order->delivery->delivery_status !== 'unassigned') {
            throw new \Exception('Delivery already in progress');
        }
    }

    private function validatePickup()
    {
        // Delivery must be assigned
        if (! $this->order->delivery || ! $this->order->delivery->driver_id) {
            throw new \Exception('Delivery not assigned yet');
        }

        // Delivery must be accepted
        if ($this->order->delivery->delivery_status !== 'accepted') {
            throw new \Exception('Driver must accept delivery first');
        }
    }

    private function validateDelivery()
    {
        // Delivery must be picked up
        if (! $this->order->delivery || $this->order->delivery->delivery_status !== 'picked_up') {
            throw new \Exception('Order must be picked up first');
        }
    }

    private function validateFailure()
    {
        // Check if max retries exceeded
        $failureCount = $this->order->statusHistories()
            ->where('new_status', 'failed_delivery')
            ->count();

        if ($failureCount >= 3) {
            throw new \Exception('Maximum delivery attempts reached');
        }
    }

    private function validateCancellation()
    {
        // Can only cancel if in early stages
        $cancellableStates = ['pending', 'confirmed'];

        if (! in_array($this->order->status, $cancellableStates)) {
            // Still allow but with reduced refund
            $refundPercentage = match ($this->order->status) {
                'preparing' => 80,
                'ready_for_pickup' => 50,
                default => 0,
            };

            // Store refund percentage for payment processing
            $this->order->setAttribute('refund_percentage', $refundPercentage);
        }
    }

    private function validateRejection()
    {
        // Can only reject if still pending
        if ($this->order->status !== 'pending') {
            throw new \Exception('Can only reject pending orders');
        }
    }

    /**
     * Get all possible next states
     */
    public function getNextStates(): array
    {
        return self::TRANSITIONS[$this->order->status] ?? [];
    }

    /**
     * Get all valid states
     */
    public static function getValidStates(): array
    {
        return array_keys(self::TRANSITIONS);
    }
}
