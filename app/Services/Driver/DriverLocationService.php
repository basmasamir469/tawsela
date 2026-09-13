<?php

namespace App\Services\Driver;

use Illuminate\Support\Facades\Redis;

class DriverLocationService
{
    /**
     * Store/update driver location in Redis.
     * Expires after 3 minutes to allow stale cleanup.
     *
     * @param int $driverId
     * @param float|string $latitude
     * @param float|string $longitude
     * @return bool
     */
    public function updateLocation($driverId, $latitude, $longitude): bool
    {
        Redis::geoadd('drivers:locations', $longitude, $latitude, $driverId);
    
        Redis::setex("driver:last_seen:{$driverId}", 180, now()->toDateTimeString());
    
        return true;
    }

    /**
     * Retrieve driver location from Redis.
     *
     * @param int $driverId
     * @return array|null
     */
     public function removeDriver($driverId): ?array
     {

      return Redis::pipeline(function ($pipe) use ($driverId) {
            $pipe->zrem('drivers:locations', $driverId);
            $pipe->del("driver:last_seen:{$driverId}");
        });
         
     }
}
