<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', 'SocialController@home');

Route::get('login/{provider}', 'SocialController@redirectToProvider');
Route::get('login/{provider}/callback', 'SocialController@handleProviderCallback');
