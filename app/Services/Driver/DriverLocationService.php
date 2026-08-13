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
        // 1. حفظ اللوكيشن في الـ Geo Set (بيسمح بالـ geosearch)
        Redis::geoadd('drivers:locations', $longitude, $latitude, $driverId);
    
        // 2. تتبع "آخر تحديث" في key منفصل، بـ TTL (زي فكرتك بالظبط، بس بغرض مختلف)
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
