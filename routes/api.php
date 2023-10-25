<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['prefix'=>'v1','namespace'=>'Api'],function(){

    Route::post('register','AuthController@register');
    Route::post('verify-user','AuthController@verifyUser');
    Route::post('login','AuthController@login');
    Route::post('forget-password','AuthController@forgetPassword');
    Route::post('reset-password/checkcode','AuthController@checkResetPasswordCode');
    Route::post('reset-password','AuthController@resetPassword');


    Route::group(['middleware'=>'auth:sanctum'],function(){
         
        Route::post('logout','AuthController@logout');

        Route::group(['namespace'=>'Driver','middleware'=>'role:driver'],function(){

            Route::post('driver-documents' ,'DriverController@driverDocuments');
            Route::post('vehicle-documents','DriverController@vehicleDocuments');
            Route::get('car-types','DriverController@carTypes');
            Route::get('car-brands','DriverController@carBrands');
            Route::get('car-colors','DriverController@carColors');
            Route::get('model-years','DriverController@modelYears');
            Route::post('activate','DriverController@activate');
            Route::post('current-location','DriverController@currentLocation');
            Route::get('driver-details','DriverController@show');
            Route::get('pending-orders','DriverController@pendingOrders');

        });


        Route::group(['namespace'=>'User','middleware'=>'role:user'],function(){

            Route::post('addresses','AddressController@store');
            Route::get('addresses','AddressController@index');
            Route::get('drive-vehicles','UserController@driveVehicles');
            Route::post('make-order','UserController@makeOrder');
        });

    });
});
