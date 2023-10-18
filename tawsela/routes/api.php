<?php

use App\Http\Controllers\Api\Driver\AuthController;
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


Route::group(['prefix'=>'v1/drivers','namespace'=>'Api\Driver'],function(){

    Route::post('register','AuthController@register');
    Route::post('verify-user','AuthController@verifyUser');
    Route::post('login','AuthController@login');
    Route::post('forget-password','AuthController@forgetPassword');
    Route::post('reset-password/checkcode','AuthController@checkResetPasswordCode');
    Route::post('reset-password','AuthController@resetPassword');
    Route::get('login/{provider}', [AuthController::class,'redirectToProvider']);
    Route::get('login/{provider}/callback', [AuthController::class,'handleProviderCallback']);



    Route::group(['middleware'=>'auth:sanctum'],function(){
         
        Route::post('logout','AuthController@logout');
    });
});
