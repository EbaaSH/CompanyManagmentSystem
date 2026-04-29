<?php

namespace App\Jobs;

use App\Events\DriverAssigned;
use App\Models\Driver\DriverProfile;
use App\Models\Notification;
use App\Models\Order\Order;
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

    public function handle()
    {
        // if ($this->order->status !== 'ready_for_pickup') {
        //     return;
        // }

        // if ($this->order->delivery->delivery_status !== 'unassigned' && $this->order->delivery->delivery_status !== 'rejected') {
        //     return;
        // }
        // \Log::info('reach the first', [
        //     'order_id' => $this->order->id,
        // ]);
        $driver = $this->findBestDriver();
        // \Log::info('Driver', [
        //     'order_id' => $this->order->id,
        //     'driver' => $driver,
        // ]);
        if ($driver) {
            // \Log::info('Driver driver find', [
            //     'order_id' => $this->order->id,
            //     'driver' => $driver,
            // ]);
            $this->order->update(['driver_id' => $driver->id]);
            $this->assignDriver($driver);
        } else {
            if ($this->attempts() >= $this->tries) {
                $this->notifyAdminNoDriverAvailable();
            }
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

    private function assignDriver(DriverProfile $driver)
    {
        try {
            DB::beginTransaction();

            $this->order->delivery->update([
                'driver_id' => $driver->id,
                'delivery_status' => 'assigned',
                'assigned_at' => now(),
            ]);

            $driver->setAvailability('busy');

            $this->order->delivery->recordStatusHistory(
                $this->order->delivery->delivery_status,
                'assigned',
                1,
                "Auto-assigned driver {$driver->user->name}"
            );

            DB::commit();

            event(new DriverAssigned($this->order->delivery));

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

    private function notifyAdminNoDriverAvailable()
    {
        $branchManager = $this->order->branch->manager;

        Notification::create([
            'user_id' => $branchManager->id,
            'type' => 'alert.no_drivers',
            'title' => "No drivers available for order #{$this->order->order_number}",
            'message' => 'Order has been waiting 10 minutes. Manual intervention needed.',
        ]);
        \Log::warning('No drivers available after timeout', [
            'order_id' => $this->order->id,
            'branch_id' => $this->order->branch_id,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('AssignDriverJob failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        $this->notifyAdminNoDriverAvailable();
    }
}
