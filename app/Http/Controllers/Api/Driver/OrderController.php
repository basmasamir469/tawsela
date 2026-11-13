<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\drivers\CancelOrderRequest;
use App\Http\Requests\drivers\RejectOrderRequest;
use App\Models\Order;
use App\Services\Driver\DriverOrderService;
use App\Services\Driver\DriverCallService;
use App\Services\Driver\DriveInvoiceService;
use App\Transformers\OrderTransformer;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected DriverOrderService $driverOrderService,
        protected DriverCallService $driverCallService,
        protected DriveInvoiceService $driveInvoiceService
    )
    {
    }

    public function reject(RejectOrderRequest $request, Order $order)
    {
        $this->driverOrderService->rejectOffer($request->user(), $order, $request->validated());

        return $this->dataResponse(null, __('order is rejected successfully'), 201);
    }

    public function pendingOrders()
    {
        $orders = fractal()->collection($this->driverOrderService->pendingOrders(auth()->user()))->transformWith(new OrderTransformer())->toArray();

        return $this->dataResponse($orders, 'pending orders', 200);
    }

    public function acceptOrder($id)
    {
        $result = $this->driverOrderService->acceptOrder(auth()->user(), (int) $id);
        if ($result === 'unavailable') {
            return $this->dataResponse(null, __('driver is not available'), 422);
        }
        if (! $result) {
            return $this->dataResponse(null, __('this order is not available now'), 422);
        }

        return $this->dataResponse(null, __('order is accepted successfully'), 200);
    }

    public function show($id)
    {
        return $this->dataResponse($this->driverOrderService->orderDetails((int) $id), 'order_details', 200);
    }

    public function cancelOrder(CancelOrderRequest $request, $id)
    {
        if (! $this->driverOrderService->cancelOrder((int) $id, $request->validated())) {
            return $this->dataResponse(null, __('this order cannot be cancelled'), 422);
        }

        return $this->dataResponse(null, __('order is cancelled successfully'), 200);
    }

    public function startDrive($id)
    {
        $started = $this->driverOrderService->startDrive((int) $id);

        return $this->dataResponse(null, $started ? __('drive is started') : __('drive cannot be started'), $started ? 200 : 422);
    }

    public function finishDrive(Request $request, $id)
    {
        $finished = $this->driverOrderService->finishDrive((int) $id, (float) ($request->input('waiting_price') ?: 0));

        return $this->dataResponse(null, $finished ? __('drive is finished') : __('drive cannot be finished'), $finished ? 200 : 422);
    }

    public function showDriveInvoice($id)
    {
        $invoice = $this->driveInvoiceService->details((int) $id);

        return $this->dataResponse($invoice, __('drive invoice'), 200);
    }

    public function completeDrive($id)
    {
        $completed = $this->driverOrderService->completeDrive((int) $id);

        return $this->dataResponse(null, $completed ? __('drive is completed successfully') : __('drive cannot be completed'), $completed ? 200 : 422);
    }

    public function call($order_id)
    {
        $this->driverCallService->callCustomer((int) $order_id, auth()->user());
    }

    public function arrived($id)
    {
        if (! $this->driverOrderService->markArrived((int) $id)) {
            return $this->dataResponse(null, __('drive cannot be marked as arrived'), 422);
        }

        return $this->dataResponse(null, __('arrived successfully'), 200);
    }
}
