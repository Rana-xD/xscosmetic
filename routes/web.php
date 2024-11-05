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
Route::get('/', 'POSController@show');

Route::get('login', 'LoginController@showLogin')->name('login');
Route::post('login', 'LoginController@login');
Route::get('logout', 'LoginController@logout')->name('logout');

Route::get('/product', 'ProductController@show');
Route::post('/product/add', 'ProductController@store');
Route::get('/product/delete', 'ProductController@destroy');
Route::post('/product/update', 'ProductController@update');


Route::get('/income-report', 'SaleController@show');
Route::get('/income-report/filter', 'SaleController@cusomterIncomeReport');
Route::get('/invoice', 'SaleController@showInvoice');
Route::get('/invoice/filter', 'SaleController@showCustomInvoice');

Route::get('/pos', 'POSController@show')->name('pos');
Route::get('/pos/add', 'POSController@store')->name('pos');
Route::get('/pos/get-invoice-no', 'POSController@getInvoiceNo')->name('pos');
Route::get('/pos/daily', 'POSController@printTotalInvoiceDaily')->name('pos');

Route::get('/order', 'OrderController@show');
Route::get('/order/update', 'OrderController@update');


Route::get('/category', 'CategoryController@show');
Route::post('/category/add', 'CategoryController@store');
Route::get('/category/delete', 'CategoryController@destroy');
Route::post('/category/update', 'CategoryController@update');

Route::get('/unit', 'UnitController@show');
Route::post('/unit/add', 'UnitController@store');
Route::get('/unit/delete', 'UnitController@destroy');
Route::post('/unit/update', 'UnitController@update');

Route::get('/change', 'ChangeController@show');
Route::post('/change/add', 'ChangeController@store');
Route::get('/change/delete', 'ChangeController@destroy');
Route::post('/change/update', 'ChangeController@update');

Route::get('/expense', 'ExpenseController@show');
Route::get('/expense/add', 'ExpenseController@store');
Route::get('/expense/delete', 'ExpenseController@destroy');
Route::post('/expense/update', 'ExpenseController@update');
Route::get('/expense/filter', 'ExpenseController@showCustomExpense');

Route::get('/delivery', 'DeliveryController@show');
Route::post('/delivery/add', 'DeliveryController@store');
Route::get('/delivery/delete', 'DeliveryController@destroy');
Route::post('/delivery/update', 'DeliveryController@update');


Route::get('/user', 'UserController@show');
Route::post('/user/add', 'UserController@store');
Route::get('/user/delete', 'UserController@destroy');
Route::post('/user/update', 'UserController@update');

Route::get('/product-income', 'ProductIncomeController@show');

Route::get('/setting', 'SettingController@show');
Route::post('/setting/update', 'SettingController@update');
