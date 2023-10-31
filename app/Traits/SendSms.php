<?php

namespace App\Traits;

use GuzzleHttp\Client;
use SendinBlue\Client\Api\TransactionalSMSApi;
use SendinBlue\Client\Configuration;
use Vonage\Client as VonageClient;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

trait SendSms
{

    public function sendSms($recepient,$code)
    {

        $basic  = new Basic(env('VONAGE_API_KEY'), env('VONAGE_API_SECRET_KEY'));
        $client = new VonageClient($basic);

        $response = $client->sms()->send(
            new SMS('+2'.$recepient, env("APP_NAME"),"your activation code is ".$code)
        );
        
        $message = $response->current();
        
        if ($message->getStatus() == 0) {
            echo "The message was sent successfully\n";
        } else {
            echo "The message failed with status: " . $message->getStatus() . "\n";
        }
        // $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));

        // $apiInstance = new TransactionalSMSApi(
        //     new Client(),
        //     $config
        // );
        // $sendTransacSms = new \SendinBlue\Client\Model\SendTransacSms();
        // $sendTransacSms['sender'] = env('APP_NAME');
        // $sendTransacSms['recipient'] = $recepient;
        // $sendTransacSms['content'] = 'your verification code is '.$code;
        // $sendTransacSms['type'] = 'transactional';
        
        // try {
        //     $result = $apiInstance->sendTransacSms($sendTransacSms);
        //     print_r($result);
        // } catch (\Exception $e) {
        //     echo 'Exception when calling TransactionalSMSApi->sendTransacSms: ', $e->getMessage(), PHP_EOL;
        // }
    }
}
