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
    public function __construct(private Order $order) {}

    public $tries = 20;

    public $timeout = 300;

    public $backoff = [30, 30, 30, 30, 30];

    /**
     * Execute job: Find and assign driver
     */
    public function handle()
    {
        if ($this->order->status !== 'ready_for_pickup') {
            return;
        }

        if ($this->order->delivery->delivery_status !== 'unassigned' && $this->order->delivery->delivery_status !== 'rejected') {
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
                $this->order->delivery->delivery_status,
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
