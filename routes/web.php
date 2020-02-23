<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/','POSController@show');

Route::get('login', 'LoginController@showLogin')->name('login');
Route::post('login', 'LoginController@login');
Route::get('logout', 'LoginController@logout')->name('logout');

Route::get('/product', 'ProductController@show');
Route::post('/product/add', 'ProductController@store');
Route::get('/product/delete','ProductController@destroy');

Route::get('/sale', 'SaleController@show');

Route::get('/pos', 'POSController@show')->name('pos');
Route::get('/pos/add', 'POSController@store')->name('pos');

Route::get('/order','OrderController@show');
Route::get('/order/update','OrderController@update');



