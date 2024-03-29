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
Route::post('/product/update','ProductController@update');


Route::get('/income-report', 'SaleController@show');
Route::get('/income-report/filter','SaleController@cusomterIncomeReport');


Route::get('/pos', 'POSController@show')->name('pos');
Route::get('/pos/add', 'POSController@store')->name('pos');
Route::get('/pos/daily','POSController@printTotalInvoiceDaily')->name('pos');

Route::get('/order','OrderController@show');
Route::get('/order/update','OrderController@update');


Route::get('/category','CategoryController@show');
Route::post('/category/add','CategoryController@store');
Route::get('/category/delete','CategoryController@destroy');
Route::post('/category/update','CategoryController@update');

Route::get('/unit','UnitController@show');
Route::post('/unit/add','UnitController@store');
Route::get('/unit/delete','UnitController@destroy');
Route::post('/unit/update','UnitController@update');

Route::get('/product-income','ProductIncomeController@show');
