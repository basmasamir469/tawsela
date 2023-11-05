<?php
	namespace App\Traits;

	trait SendNotification{
          
		function notifyByFirebase($tokens,$data = [],$type=null)      
        {
            $fcmMsg = array(
                'body' => $data['body']??'',
                'title' => $data['title']??'',
                'sound' => "default",
                'color' => "#203E78"
            );
            $iosFcmFields = array(
                'registration_ids' => $tokens,
                'topic'  => 'welcome-bonus',
                'priority' => 'high',
                "content_available"=> true,
                'notification' => $fcmMsg,
                'data' => $data
            );
            $androidFcmFields = array(
                'registration_ids' => $tokens,
                'topic'  => 'welcome-bonus',
                'priority' => 'high',
                "content_available"=> true,
                'notification' => $fcmMsg,
                'data' => $data
            );

            $silentFcmFields = array(
                'registration_ids' => $tokens,
                'priority' => 'high',
                "content_available"=> true,
                "aps" =>[
                    "content-available"=>"1"
                ],
                'data' => $data
            );
            $firebase_key=env('FIREBASE_ACCESS_KEY');

            $headers = array(
                 'Authorization: key='.$firebase_key,
                 'Content-Type: application/json'
             );
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(($type == "silent")? $silentFcmFields :(($type == "android")? $androidFcmFields :$iosFcmFields)));
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
             
             
	}
