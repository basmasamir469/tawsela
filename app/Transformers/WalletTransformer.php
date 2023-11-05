<?php

namespace App\Transformers;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class WalletTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];
    
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];
    
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(User $user)
    {
        $orders = $user->driverOrders()->filterByDate()->get();
        $pending_orders = $user->pendingOrders()->filterByDate()->count();
        $cancelled_orders = $orders->where('order_status',Order::CANCELLED)->count();
        $completed_orders = $orders->where('order_status',Order::COMPLETED)->count();
        $unapproved_orders = $user->unapprovedOrders()->filterByDate()->count();
        $accepted_orders = $orders->whereIn('order_status',[Order::ACCEPTED,Order::STARTED,Order::FINISHED,Order::COMPLETED,Order::INWAY])->count();
        return [
            'total_cost'         =>$orders->sum('total_cost'),
            'accepted_orders'    =>$accepted_orders,
            'cancelled_orders'   =>$cancelled_orders,
            'completed_orders'   =>$completed_orders,
            'unapproved_orders'  =>$unapproved_orders,
            'acceptance rate'    =>round(($accepted_orders/($pending_orders+$unapproved_orders+$accepted_orders+$cancelled_orders))*100,1) .'%'
        ];
    }
}
