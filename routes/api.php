<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// API PUBLIC
// User
Route::group(['prefix'=>'user'], function(){
    Route::get('active-account', 'Api\UserController@activeAccount');
    Route::post('login', 'Api\UserController@login');
});

// API NEED TO LOGIN
Route::middleware('auth:api')->group(function (){
    // User
    Route::group(['prefix'=>'user'], function(){
        Route::post('register', 'Api\UserController@register');
        Route::post('logout', 'Api\UserController@logout');
        Route::get('profile', 'Api\UserController@getProfile');
        Route::get('list-user', 'Api\UserController@getListUser');
        Route::post('change-active-status', 'Api\UserController@changeActiveStatus');
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
