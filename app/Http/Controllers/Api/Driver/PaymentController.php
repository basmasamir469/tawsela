<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\drivers\PaymentRequest;
use Srmklive\PayPal\Services\ExpressCheckout;
use Illuminate\Http\Request;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function stripe(PaymentRequest $request)
    {
      try{
        $data = $request->validated();
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $card = $stripe->tokens->create([
            'card' => [
              'number' => $data['card-number'] ,
              'exp_month' => $data['exp-month'],
              'exp_year' => $data['exp-year'],
              'cvc' => $data['cvc'],
            ],
         ]);
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $response = $stripe->charges->create([
          'amount' => (int)$request->user()->debit,
          'currency' => 'usd',
          'source' => 'tok_visa',
          'description' => $data['description'],
        ]);
       $request->user()->update([
        'debit' => 0.00,
        'account_status'=> 1
       ]);
      return $this->dataResponse(null,$response->status,200);
      }
      catch(\Exception $e)
      {
        return $this->dataResponse(null,$e->getMessage(),402);
      }
    }
    // public function payment()
    // {
    //     $data = [];
    //     $data['items'] = [
    //    [
    //    'name' => 'amounts-due',
    //    'price' => auth()->user()->debit,
    //    'desc' => 'amounts-due of a driver to the application',
    //    'qty' => 1
    //    ]
    //    ];
       
    //     $data['invoice_id'] = 1;
    //     $data['invoice_description'] = "Order #{$data['invoice_id']} Invoice";
    //     $data['return_url'] = route('payment.success');
    //     $data['cancel_url'] = route('payment.cancel');
    //     $data['total'] = auth()->user()->debit;
       
    //     $provider = new ExpressCheckout;
    //     $response = $provider->setExpressCheckout($data);
    //     $response = $provider->setExpressCheckout($data, true);
       
    //    return redirect($response['paypal_link']);
    // }

    // public function cancel()
    // {
    //     return $this->dataResponse(null,__('payment is cancelled'),402);
    // }

    // public function success(Request $request)
    // {
    //     $provider = new ExpressCheckout;
    //     $response = $provider->getExpressCheckoutDetails($request->token);
    //     if(in_array(strtoupper($response['ACK']),['SUCCESS','SUCCESSWITHWARNING']))
    //     {
    //         $request->user()->update([
    //             'debit' => 0
    //         ]);
    //         return $this->dataResponse(null,__('payment is successfully done'),200);
    //     }
    //     return $this->dataResponse(null,__('faild! please try again later'),402);

    // }
}
