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
        Route::post('submit-token','AuthController@submitToken');
        Route::post('update-profile','AuthController@updateProfile');

        Route::group(['namespace'=>'Driver','middleware'=>'role:driver'],function(){

          Route::group(['middleware' => 'is_account_opened'],function(){

            Route::post('driver-documents' ,'DriverController@driverDocuments');
            Route::post('vehicle-documents','DriverController@vehicleDocuments');
            Route::get('car-types','DriverController@carTypes');
            Route::get('car-brands','DriverController@carBrands');
            Route::get('car-colors','DriverController@carColors');
            Route::get('model-years','DriverController@modelYears');
            Route::post('activate','DriverController@activate');
            Route::post('current-location','DriverController@currentLocation');
            Route::get('driver-details','DriverController@show');
            Route::get('pending-orders','OrderController@pendingOrders');
            Route::get('phone-call/{order_id}','OrderController@call');
            Route::post('cancel-order/{order_id}','OrderController@cancelOrder');
            Route::get('accept-order/{order_id}','OrderController@acceptOrder');
            Route::get('start-drive/{order_id}','OrderController@startDrive');
            Route::post('finish-drive/{order_id}','OrderController@finishDrive');
            Route::get('complete-drive/{order_id}','OrderController@completeDrive');
            Route::get('order-details/{id}','OrderController@show');
            Route::get('drives-dates','DriverController@drivesDates');
            Route::get('drives','DriverController@drives');
            Route::get('finished-drive/{order_id}','OrderController@showFinishedDrive');
            Route::post('voice-alert','DriverController@voiceAlert');
            Route::post('activate-notifications','DriverController@activateNotifications');
            Route::get('my-wallet','DriverController@myWallet');

        });
        
            Route::post('payment','PaymentController@stripe')->name('payment.store');
        });


        Route::group(['namespace'=>'User','middleware'=>'role:user'],function(){

            Route::post('addresses','AddressController@store');
            Route::get('addresses','AddressController@index');
            Route::get('drive-vehicles','OrderController@driveVehicles');
            Route::post('make-order','OrderController@makeOrder');
        });

    });
});
