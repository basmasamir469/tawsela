<?php

namespace App\Traits;

trait Response 
{
    public function dataResponse( $data,$message=null,$code=200 ){
        $array = [
            'data'     => $data,
            'message'  => $message,
            'status'   => in_array( $code, $this->successCode() ) ? true : false,
        ];
        return response( $array,$code );
    }

    public function errorResponse($message,$errors,$code){
        $array = [
            'message' => $message,
            'errors'  => $errors,
            'status' => in_array($code, $this->successCode()) ? true : false
        ];
        return response( $array,$code );

    }

    public function successCode(){
        return [ 200 , 201, 202 ];
    }
}
