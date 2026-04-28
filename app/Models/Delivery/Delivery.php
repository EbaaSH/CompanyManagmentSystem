<?php

namespace App\Models\Delivery;

use App\Events\DeliveryAccepted;
use App\Events\DeliveryFailed;
use App\Events\DriverAssigned;
use App\Events\OrderDelivered;
use App\Events\OrderPickedUp;
use App\Events\PaymentProcessed;
use App\Jobs\AssignDriverJob;
use App\Models\Driver\DriverProfile;
use App\Models\Order\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'driver_id', 'delivery_status', 'assigned_at', 'accepted_at', 'picked_up_at', 'delivered_at', 'proof_image_url', 'delivery_notes', 'retry_attempt', 'scheduled_retry_at'];

    // ─── Relationships ────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * Uses JOIN for company/branch/customer scopes to avoid nested subqueries.
     *
     * super-admin     → all deliveries
     * company-manager → deliveries for orders in their company
     * branch-manager  → deliveries for orders in their branch
     * employee        → deliveries for orders in their branch
     * driver          → own deliveries only
     * customer        → deliveries for their own orders
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,

            $user->hasRole('company-manager') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.company_id', $user->ownedCompany->id)
                ->select('deliveries.*'),

            $user->hasRole('branch-manager') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.branch_id', $user->ownedBranch->id)
                ->select('deliveries.*'),

            $user->hasRole('employee') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.branch_id', $user->employeeProfile->branch_id)
                ->select('deliveries.*'),

            $user->hasRole('driver') => $query->where('driver_id', $user->driverProfile->id),

            $user->hasRole('customer') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.customer_id', $user->customerProfile->id)
                ->select('deliveries.*'),

            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('deliveries.scope.all') => $query,

            $user->can('deliveries.scope.company') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.company_id', $user->resolveCompanyId())
                ->select('deliveries.*'),

            $user->can('deliveries.scope.branch') => $query
                ->join('orders', 'orders.id', '=', 'deliveries.order_id')
                ->where('orders.branch_id', $user->resolveBranchId())
                ->select('deliveries.*'),

            $user->can('deliveries.scope.own') => $this->resolveOwnDelivery($query, $user),

            default => $query->whereRaw('0 = 1'),
        };
    }

    private function resolveOwnDelivery(Builder $query, User $user): Builder
    {
        // driver → own deliveries
        if ($user->driverProfile) {
            return $query->where('driver_id', $user->driverProfile->id);
        }

        // customer → deliveries for their orders
        return $query
            ->join('orders', 'orders.id', '=', 'deliveries.order_id')
            ->where('orders.customer_id', $user->customerProfile->id)
            ->select('deliveries.*');
    }

    // ===== DELIVERY WORKFLOW METHODS =====

    /**
     * ASSIGN DELIVERY TO DRIVER
     * Called by AssignDriverJob when driver is available
     */
    public function assign(DriverProfile $driver, $userId = null)
    {
        if ($this->delivery_status !== 'unassigned') {
            throw new \Exception('Delivery cannot be assigned in current status');
        }

        $this->update([
            'driver_id' => $driver->id,
            'delivery_status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $this->recordStatusHistory('unassigned', 'assigned', $userId ?? 1);

        // Fire event
        event(new DriverAssigned($this));

        return $this;
    }

    /**
     * DRIVER ACCEPTS DELIVERY
     * Called when driver accepts the delivery assignment
     */
    // public function accept($userId)
    // {
    //     if ($this->delivery_status !== 'assigned') {
    //         throw new \Exception('Delivery must be assigned before accepting');
    //     }

    //     $this->update([
    //         'delivery_status' => 'accepted',
    //         'accepted_at' => now(),
    //     ]);

    //     $this->recordStatusHistory('assigned', 'accepted', $userId);

    //     // Fire event
    //     event(new DeliveryAccepted($this));

    //     return $this;
    // }

    /**
     * DRIVER REJECTS DELIVERY
     * Called when driver rejects the delivery
     * Triggers auto-reassignment
     */
    // public function reject($userId, $reason = null)
    // {
    //     if ($this->delivery_status !== 'assigned') {
    //         throw new \Exception('Can only reject assigned deliveries');
    //     }

    //     $this->update([
    //         'delivery_status' => 'rejected',
    //         'driver_id' => null, // Clear driver
    //     ]);

    //     $this->recordStatusHistory('assigned', 'rejected', $userId, $reason);

    //     // Immediately trigger re-assignment
    //     AssignDriverJob::dispatch($this->order);

    //     return $this;
    // }

    /**
     * DRIVER PICKS UP ORDER
     * Called when driver physically picks up from branch
     */
    // public function pickUp($userId)
    // {

    //     $this->update([
    //         'delivery_status' => 'picked_up',
    //         'picked_up_at' => now(),
    //     ]);

    //     $this->recordStatusHistory('accepted', 'picked_up', $userId);

    //     // Fire event
    //     event(new OrderPickedUp($this->order));

    //     return $this;
    // }

    /**
     * DRIVER DELIVERS ORDER
     * Called when driver delivers to customer
     */
    // public function deliver($userId, $proofImageUrl = null, $notes = null)
    // {
    //     if ($this->delivery_status !== 'picked_up') {
    //         throw new \Exception('Order must be picked up before delivery');
    //     }

    //     $this->update([
    //         'delivery_status' => 'delivered',
    //         'delivered_at' => now(),
    //         'proof_image_url' => $proofImageUrl,
    //         'delivery_notes' => $notes,
    //     ]);

    //     $this->recordStatusHistory('picked_up', 'delivered', $userId);

    //     // Update order status
    //     $this->order->update(['status' => 'delivered']);
    //     $this->order->recordStatusHistory('picked_up', 'delivered', $userId);

    //     // Process payment
    //     $this->processPayment();

    //     // Fire event
    //     event(new OrderDelivered($this->order));

    //     return $this;
    // }

    /**
     * DELIVERY FAILED
     * Called when delivery attempt fails (customer not home, etc.)
     * Triggers automatic retry logic
     */
    // public function fail($userId, $reason = null)
    // {
    //     if ($this->delivery_status !== 'picked_up') {
    //         throw new \Exception('Can only fail deliveries that are picked up');
    //     }

    //     $this->increment('retry_attempt');

    //     // Max 3 retry attempts
    //     if ($this->retry_attempt >= 3) {
    //         $this->update(['delivery_status' => 'failed']);
    //         $this->order->update(['status' => 'failed_delivery']);
    //         $this->order->recordStatusHistory('picked_up', 'failed_delivery', $userId, "Delivery failed after {$this->retry_attempt} attempts. Reason: {$reason}");

    //         // Notify customer of options (refund, reschedule, pickup)
    //         event(new DeliveryFailed($this, $reason));

    //         return $this;
    //     }

    //     // Schedule retry
    //     $retryTime = match ($this->retry_attempt) {
    //         1 => now()->addHours(2), // Retry in 2 hours
    //         2 => now()->addDay(), // Retry next day
    //         default => now()->addHours(4),
    //     };

    //     $this->update([
    //         'delivery_status' => 'unassigned',
    //         'driver_id' => null,
    //         'scheduled_retry_at' => $retryTime,
    //     ]);

    //     $this->recordStatusHistory('picked_up', 'unassigned', $userId, "Failed delivery - Retry {$this->retry_attempt}. Reason: {$reason}");

    //     // Fire event
    //     event(new DeliveryFailed($this, $reason));

    //     // Queue retry job for scheduled time
    //     AssignDriverJob::dispatch($this->order)->delay($retryTime);

    //     return $this;
    // }

    /**
     * PROCESS PAYMENT
     * Mark payment as paid after successful delivery
     */
    // private function processPayment()
    // {
    //     $payment = $this->order->payment ?? Payment::where('order_id', $this->order_id)->first();

    //     if ($payment && $payment->payment_status === 'pending') {
    //         $payment->update([
    //             'payment_status' => 'paid',
    //             'paid_at' => now(),
    //         ]);

    //         // Fire event
    //         event(new PaymentProcessed($payment));
    //     }
    // }

    /**
     * Record delivery status history (audit trail)
     */
    public function recordStatusHistory($oldStatus, $newStatus, $userId, $reason = null)
    {
        return DeliveryStatusHistory::create([
            'delivery_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $userId,
            'reason' => $reason ?? '',
        ]);
    }
}
