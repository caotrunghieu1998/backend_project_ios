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

// API user api public
Route::group(['prefix'=>'user'], function(){
    Route::get('/active-account', 'Api\UserController@activeAccount');
});

Route::middleware('auth:api')->group(function (){
    Route::post('/register', 'Api\UserController@register');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
