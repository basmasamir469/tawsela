<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\Driver\DriverLocationService;
use Illuminate\Http\Request;

class DriverLocationController extends Controller
{
    protected DriverLocationService $service;

    public function __construct(DriverLocationService $service)
    {
        $this->service = $service;
    }

    /**
     * Receive driver location updates (called every minute by the client).
     */
    public function update(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $driver = $request->user();

        $this->service->updateLocation($driver->id, $request->latitude, $request->longitude);

        return $this->dataResponse(null, __('location updated successfully'), 200);
    }

    public function goOffline(Request $request)
    {
        $driver = $request->user();

        $this->service->removeDriver($driver->id);

        return $this->dataResponse(null, __('driver is now offline'), 200);
    }
}
