<?php

namespace App\Services\Driver;

use App\Models\Order;
use App\Models\User;
use Vonage\Client;

class DriverCallService
{
    public function callCustomer(int $orderId, User $driver): void
    {
        $order = Order::with('user')->findOrFail($orderId);
        $keypair = new \Vonage\Client\Credentials\Keypair(
            file_get_contents('private.key'),
            env('VONAGE_APPLICATION_ID')
        );
        $client = new Client($keypair);
        $call = new \Vonage\Voice\OutboundCall(
            new \Vonage\Voice\Endpoint\Phone('+2'.$order->user->phone),
            new \Vonage\Voice\Endpoint\Phone('+2'.$driver->phone)
        );
        $call->setAnswerWebhook(new \Vonage\Voice\Webhook(
            'https://raw.githubusercontent.com/nexmo-community/ncco-examples/gh-pages/text-to-speech.json',
            \Vonage\Voice\Webhook::METHOD_GET
        ));

        $client->voice()->createOutboundCall($call);
    }
}
