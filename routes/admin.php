<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'v1','middleware'=>'auth:sanctum','namespace'=>'Api\Admin'],function(){
 
    Route::get('car_types','CarTypeController@index')->name('car_types.index');
    Route::get('car_types/{car_type}','CarTypeController@show')->name('car_types.show');
    Route::post('car_types','CarTypeController@store')->name('car_types.store');
    Route::put('car_types/{car_type}','CarTypeController@update')->name('car_types.update');
    Route::delete('car_types/{car_type}','CarTypeController@destroy')->name('car_types.destroy');

    Route::get('car_brands','CarBrandController@index')->name('car_brands.index');
    Route::get('car_brands/{car_brand}','CarTypeController@show')->name('car_brands.show');
    Route::post('car_brands','CarBrandController@store')->name('car_brands.store');
    Route::put('car_brands/{car_brand}','CarBrandController@update')->name('car_brands.update');
    Route::delete('car_brands/{car_brand}','CarBrandController@destroy')->name('car_brands.destroy');

    Route::get('promotions','PromotionController@index')->name('promotions.index');
    Route::get('promotions/{promotion}','PromotionController@show')->name('promotions.show');
    Route::post('promotions','PromotionController@store')->name('promotions.store');
    Route::put('promotions/{promotion}','PromotionController@update')->name('promotions.update');
    Route::delete('promotions/{promotion}','PromotionController@destroy')->name('promotions.destroy');

    Route::post('settings','SettingController@update');

});
