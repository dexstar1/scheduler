<?php

use Illuminate\Http\Request;

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

Route::post('login', 'Api\UserController@login');
Route::post('register', 'Api\UserController@register');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('customer', 'CustomerController@index');//->middleware('cors');
Route::get('customers/{customer}', 'CustomerController@show')->middleware('auth:api');
Route::post('customers', 'CustomerController@store');//->middleware('cors');
Route::post('customers/confirmed', 'CustomerController@confirmed');
Route::put('customers/{customer}', 'CustomerController@update')->middleware('auth:api');
Route::delete('customers/{customer}', 'CustomerController@delete')->middleware('auth:api');