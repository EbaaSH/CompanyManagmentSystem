<?php

namespace App\Models\Order;

use App\Events\DeliveryFailed;
use App\Events\OrderCancelled;
use App\Events\OrderConfirmed;
use App\Events\OrderDelivered;
use App\Events\OrderPickedUp;
use App\Events\OrderPreparing;
use App\Events\OrderReady;
use App\Events\OrderRejected;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Customer\CustomerAddress;
use App\Models\Customer\CustomerProfile;
use App\Models\Delivery\Delivery;
use App\Models\Driver\DriverProfile;
use App\Models\Payment;
use App\Models\User;
use App\Services\Customer\OrderStateMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_number', 'customer_id', 'company_id', 'branch_id', 'delivery_address_id', 'driver_id', 'status', 'notes'];

    // ─── Relationships ────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderInvoice(): HasOne
    {
        return $this->hasOne(OrderInvoice::class);
    }

    public function orderStatus(): HasOne
    {
        return $this->hasOne(OrderStatus::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    // ─── Scope: Role-based ────────────────────────────────────────────

    /**
     * TECHNIQUE 1 — Role names
     *
     * super-admin     → all orders (write blocked via Policy)
     * company-manager → orders in their company
     * branch-manager  → orders in their branch
     * employee        → orders in their branch
     * driver          → only orders assigned to them (read-only via Policy)
     * customer        → own orders only
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super-admin') => $query,
            $user->hasRole('company-manager') => $query->where('company_id', $user->ownedCompany->id),
            $user->hasRole('branch-manager') => $query->where('branch_id', $user->ownedBranch->id),
            $user->hasRole('employee') => $query->where('branch_id', $user->employeeProfile->branch_id),
            $user->hasRole('driver') => $query->where('driver_id', $user->driverProfile->id),
            $user->hasRole('customer') => $query->where('customer_id', $user->customerProfile->id),
            default => $query->whereRaw('0 = 1'),
        };
    }

    /**
     * TECHNIQUE 2 — Permissions
     */
    public function scopeForUserViaPermission(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->can('orders.scope.all') => $query,
            $user->can('orders.scope.company') => $query->where('company_id', $user->resolveCompanyId()),
            $user->can('orders.scope.branch') => $query->where('branch_id', $user->resolveBranchId()),
            $user->can('orders.scope.assigned') => $query->where('driver_id', $user->driverProfile->id),
            $user->can('orders.scope.own') => $query->where('customer_id', $user->customerProfile->id),
            default => $query->whereRaw('0 = 1'),
        };
    }

    // ===== STATE MACHINE METHODS =====

    /**
     * Get state machine for this order
     */
    public function stateMachine(): OrderStateMachine
    {
        return new OrderStateMachine($this);
    }

    /**
     * Check if can transition to state
     */
    public function canTransitionTo($newStatus): bool
    {
        return $this->stateMachine()->canTransition($newStatus, auth()->user()?->getRoleNames()->first() ?? 'system');
    }

    /**
     * Get all possible next states
     */
    public function getNextStates(): array
    {
        return $this->stateMachine()->getNextStates();
    }

    // ===== WORKFLOW METHODS (OPTIMIZED) =====

    /**
     * AUTO-CONFIRM ORDER
     * Called automatically after validation
     * Skips manual confirmation step for faster processing
     */
    public function autoConfirm()
    {
        $userId = auth()->user()->id;
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('confirmed', 'system')) {
            throw new \Exception('Order cannot be auto-confirmed');
        }

        $this->update(['status' => 'confirmed']);
        $this->recordStatusHistory('pending', 'confirmed', $userId, 'Auto-confirmed by system');

        // Fire event
        event(new OrderConfirmed($this));

        return $this;
    }

    /**
     * EMPLOYEE CONFIRMS ORDER (Manual)
     * Used if auto-confirmation fails
     */
    public function confirm($userId)
    {
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('confirmed', 'employee')) {
            throw new \Exception('Order cannot be confirmed in current status');
        }

        $this->update(['status' => 'confirmed']);
        $this->recordStatusHistory('pending', 'confirmed', $userId, 'Manually confirmed by employee');

        event(new OrderConfirmed($this));

        return $this;
    }

    /**
     * EMPLOYEE MARKS PREPARING
     * Employee starts preparing order in kitchen
     */
    public function markPreparing($userId)
    {
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('preparing', 'employee')) {
            throw new \Exception('Order cannot be marked as preparing');
        }

        $this->update(['status' => 'preparing']);
        $this->recordStatusHistory('confirmed', 'preparing', $userId);

        event(new OrderPreparing($this));

        return $this;
    }

    /**
     * EMPLOYEE MARKS READY FOR PICKUP
     *
     * IMPORTANT: This triggers:
     * 1. Delivery auto-creation
     * 2. Async driver assignment job (queued)
     * 3. Customer notification
     */
    public function markReady($userId)
    {
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('ready_for_pickup', 'employee')) {
            throw new \Exception('Order cannot be marked as ready');
        }

        // Create delivery if doesn't exist
        if (! $this->delivery) {
            $this->delivery()->create([
                'delivery_status' => 'unassigned',
            ]);
        }

        $this->update(['status' => 'ready_for_pickup']);
        $this->recordStatusHistory('preparing', 'ready_for_pickup', $userId);

        // FIRE EVENT: This triggers auto-driver assignment
        event(new OrderReady($this));

        return $this;
    }

    /**
     * DRIVER PICKS UP ORDER
     * Called when driver physically picks up order from branch
     */
    public function pickUp($userId)
    {
        if (! $this->delivery || $this->delivery->delivery_status !== 'accepted') {
            throw new \Exception('Delivery must be accepted before pickup');
        }

        $this->update(['status' => 'picked_up']);
        $this->recordStatusHistory('ready_for_pickup', 'picked_up', $userId);

        $this->delivery->update([
            'delivery_status' => 'picked_up',
            'picked_up_at' => now(),
        ]);

        event(new OrderPickedUp($this));

        return $this;
    }

    /**
     * DRIVER DELIVERS ORDER
     * Called when order delivered to customer
     */
    public function deliver($userId, $proofImageUrl = null, $notes = null)
    {
        if (! $this->delivery || $this->delivery->delivery_status !== 'picked_up') {
            throw new \Exception('Order must be picked up before delivery');
        }

        $this->update(['status' => 'delivered']);
        $this->recordStatusHistory('picked_up', 'delivered', $userId);

        $this->delivery->update([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
            'proof_image_url' => $proofImageUrl,
            'delivery_notes' => $notes,
        ]);

        event(new OrderDelivered($this));

        return $this;
    }

    /**
     * DELIVERY FAILED
     * Called when delivery attempt fails (customer not home, etc.)
     */
    public function failDelivery($userId, $reason = null)
    {
        if (! $this->delivery) {
            throw new \Exception('Delivery not found');
        }

        $this->update(['status' => 'failed_delivery']);
        $this->recordStatusHistory($this->status, 'failed_delivery', $userId, $reason);

        $this->delivery->update(['delivery_status' => 'failed']);

        event(new DeliveryFailed($this->delivery, $reason));

        return $this;
    }

    /**
     * CANCEL ORDER
     * Customer or system can cancel based on order stage
     */
    public function cancel($userId, $reason = null)
    {
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('cancelled', auth()->user()?->getRoleNames()->first() ?? 'system')) {
            throw new \Exception("Cannot cancel order in {$this->status} status");
        }

        $this->update(['status' => 'cancelled']);
        $this->recordStatusHistory($this->status, 'cancelled', $userId, $reason);

        event(new OrderCancelled($this, $reason));

        return $this;
    }

    /**
     * REJECT ORDER
     * Employee/Admin rejects order before confirming
     */
    public function reject($userId, $reason = null)
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Can only reject pending orders');
        }

        $this->update(['status' => 'rejected']);
        $this->recordStatusHistory('pending', 'rejected', $userId, $reason);

        event(new OrderRejected($this, $reason));

        return $this;
    }

    // ===== HELPER METHODS =====

    /**
     * Record status history (audit trail)
     */
    public function recordStatusHistory($oldStatus, $newStatus, $userId, $reason = null)
    {
        return OrderStatusHistory::create([
            'order_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $userId,
            'reason' => $reason ?? '',
        ]);
    }

    /**
     * Get total preparation time
     */
    public function getTotalPrepTimeAttribute(): int
    {
        return $this->items->sum(fn ($item) => $item->menuItem->preparation_time_minutes ?? 0);
    }

    /**
     * Get remaining prep time based on status
     */
    public function getRemainingPrepTime(): ?int
    {
        if (! in_array($this->status, ['confirmed', 'preparing'])) {
            return null;
        }

        $createdAt = $this->created_at;
        $estimatedReady = $createdAt->addMinutes($this->getTotalPrepTime);
        $remaining = $estimatedReady->diffInMinutes(now());

        return max(0, $remaining);
    }

    /**
     * Get customer satisfaction score (based on delivery time, etc.)
     */
    public function getCustomerSatisfactionScore(): ?float
    {
        if ($this->status !== 'delivered') {
            return null;
        }

        $deliveryTime = $this->delivery->delivered_at->diffInMinutes($this->created_at);
        $estimatedTime = $this->getTotalPrepTime + 30; // 30 min delivery

        // Score based on time efficiency
        if ($deliveryTime <= $estimatedTime) {
            return 95;
        } elseif ($deliveryTime <= $estimatedTime + 15) {
            return 85;
        } else {
            return 70;
        }
    }

    /**
     * Check if order needs emergency attention
     */
    public function needsAttention(): bool
    {
        // Order stuck for more than 30 minutes
        if (in_array($this->status, ['ready_for_pickup', 'picked_up'])) {
            return $this->updated_at->diffInMinutes(now()) > 30;
        }

        // Order waiting for driver for more than 10 minutes
        if ($this->status === 'ready_for_pickup' && ! $this->driver) {
            return $this->statusHistories()
                ->where('new_status', 'ready_for_pickup')
                ->first()
                ->created_at
                ->diffInMinutes(now()) > 10;
        }

        return false;
    }
}
