<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\drivers\CancelOrderRequest;
use App\Http\Requests\users\OrderRequest;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function index(Request $request)
    {
        $skip = $request->skip ?? 0;
        $take = $request->take ?? 10;

        $orders = $this->orderService->listOrders($request->user(), $skip, $take);

        return $this->dataResponse($orders, __('orders'), 200);
    }

    public function driveVehicles()
    {
        $vehicles = $this->orderService->driveVehicles();

        return $this->dataResponse($vehicles, 'all drive vehicles', 200);
    }

    public function makeOrder(OrderRequest $request)
    {
        try {
            $result = $this->orderService->makeOrder($request->user(), $request->validated());
            return $this->dataResponse($result, __('order is sent successfully'), 200);
            
        } catch (\RuntimeException $e) {
            return $this->dataResponse(null, $e->getMessage(), 422);
        }
    }

    public function cancelOrder(CancelOrderRequest $request, $id)
    {
        $data = $request->validated();

        if ($this->orderService->cancelOrder($request->user(), (int) $id, $data)) {
            return $this->dataResponse(null, __('order is cancelled successfully'), 200);
        }

        return $this->dataResponse(null, __('order not found or cannot be cancelled'), 422);
    }

    public function show($id)
    {
        $order = $this->orderService->showOrder((int) $id);

        return $this->dataResponse($order, 'drive_details', 200);
    }
}