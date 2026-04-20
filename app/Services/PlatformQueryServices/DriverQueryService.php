<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Driver\DriverProfile;

class DriverQueryService
{
    public function getDriverById($driverId)
    {
        $user = auth()->user();
        $driver = DriverProfile::query()
            ->forUserViaPermission($user)
            ->with('user',
                'company',
                'branch',
                'orders',
                'deliveries')->find($driverId);
        if (! $driver) {
            return [
                'data' => null,
                'message' => 'driver not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $driver,
            'message' => 'driver retrevied successfully',
            'code' => 200,
        ];

    }

    public function getAllDrivers()
    {
        $user = auth()->user();
        $driver = DriverProfile::query()
            ->forUserViaPermission($user)
            ->with('user',
                'company',
                'branch',
                'orders',
                'deliveries')->paginate(10);
        if (! $driver) {
            return [
                'data' => null,
                'message' => 'driver not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $driver,
            'message' => 'driver retrevied successfully',
            'code' => 200,
        ];
    }
}
