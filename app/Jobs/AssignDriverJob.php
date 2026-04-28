<?php

namespace App\Jobs;

use App\Events\DriverAssigned;
use App\Models\Driver\DriverProfile;
use App\Models\Notification;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AssignDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Order $order)
    {
    }

    public $tries = 20; // Max 20 retry attempts

    public $timeout = 300; // 5 minutes per attempt

    public $backoff = [30, 30, 30, 30, 30]; // 30 seconds between retries

    /**
     * Execute job: Find and assign driver
     */
    public function handle()
    {
        // If order no longer ready, skip
        if ($this->order->status !== 'ready_for_pickup') {
            return;
        }

        // If delivery already assigned, skip
        if ($this->order->delivery->delivery_status !== 'unassigned') {
            return;
        }

        // Find best available driver
        $driver = $this->findBestDriver();

        if ($driver) {
            $this->order->update(['driver_id' => $driver->id]);
            $this->assignDriver($driver);
        } else {
            // No driver available - will retry via Illuminate queue
            if ($this->attempts() >= $this->tries) {
                // Max retries exceeded - notify admin
                $this->notifyAdminNoDriverAvailable();
            }

            // Throw exception to trigger retry
            throw new \Exception("No available drivers for order #{$this->order->order_number}");
        }
    }

    /**
     * Find best driver using load balancing
     * Criteria:
     * 1. Available status
     * 2. Active
     * 3. Same branch
     * 4. Least current deliveries (load balanced)
     * 5. Closest to branch (optional, with geolocation)
     */
    private function findBestDriver(): ?DriverProfile
    {
        return DriverProfile::where('branch_id', $this->order->branch_id)
            ->where('availability_status', 'available')
            ->where('is_active', true)
            ->withCount('deliveries')
            ->orderBy('deliveries_count', 'asc') // Load balance
            ->orderByRaw('ABS(current_latitude - ?) + ABS(current_longitude - ?)', [
                $this->order->branch->latitude,
                $this->order->branch->longitude,
            ]) // Closest driver
            ->first();
    }

    /**
     * Assign driver to delivery
     */
    private function assignDriver(DriverProfile $driver)
    {
        try {
            DB::beginTransaction();

            // Assign driver to delivery
            $this->order->delivery->update([
                'driver_id' => $driver->id,
                'delivery_status' => 'assigned',
                'assigned_at' => now(),
            ]);

            // Set driver to busy
            $driver->setAvailability('busy');

            // Record status history
            $this->order->delivery->recordStatusHistory(
                'unassigned',
                'assigned',
                1, // system user
                "Auto-assigned driver {$driver->user->name}"
            );

            DB::commit();

            // Fire event
            event(new DriverAssigned($this->order->delivery));

            // Notify driver
            // $this->notifyDriver($driver);

            // Notify customer
            // $this->notifyCustomer();

            \Log::info('Driver assigned', [
                'order_id' => $this->order->id,
                'driver_id' => $driver->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to assign driver', [
                'error' => $e->getMessage(),
                'order_id' => $this->order->id,
            ]);
            throw $e;
        }
    }

    /**
     * Notify driver of assignment
     */
    // private function notifyDriver(DriverProfile $driver)
    // {
    //     Notification::create([
    //         'user_id' => $driver->user_id,
    //         'type' => 'delivery.assigned',
    //         'title' => 'New Delivery: '.$this->order->order_number,
    //         'message' => "Pick up your items from {$this->order->branch->name}",
    //     ]);

    //     // Send push notification with delivery details
    //     // $driver->user->notifyNewDelivery($this->order);
    // }

    /**
     * Notify customer driver assigned
     */
    // private function notifyCustomer()
    // {
    //     $driver = $this->order->delivery->driver;
    //     $estimatedPickupTime = 15; // minutes
    //     $estimatedDeliveryTime = 30; // minutes

    //     Notification::create([
    //         'user_id' => $this->order->customer->user_id,
    //         'type' => 'delivery.assigned',
    //         'title' => 'Driver assigned!',
    //         'message' => "Driver {$driver->user->name} will deliver your order in ~{$estimatedDeliveryTime} minutes",
    //     ]);

    //     // Send tracking link
    //     // $this->order->customer->user->sendTrackingLink($this->order);
    // }

    /**
     * Notify admin when no driver available after timeout
     */
    private function notifyAdminNoDriverAvailable()
    {
        $superAdmins = User::role('super_admin')->get();

        foreach ($superAdmins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'alert.no_drivers',
                'title' => "No drivers available for order #{$this->order->order_number}",
                'message' => 'Order has been waiting 10 minutes. Manual intervention needed.',
            ]);
        }

        \Log::warning('No drivers available after timeout', [
            'order_id' => $this->order->id,
            'branch_id' => $this->order->branch_id,
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('AssignDriverJob failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // Notify admin of permanent failure
        $this->notifyAdminNoDriverAvailable();
    }
}
