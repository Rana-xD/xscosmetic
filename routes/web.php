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
Route::post('/invoice/update-payment', 'SaleController@updatePaymentType');
Route::get('/invoice/get-delivery-id', 'SaleController@getDeliveryId');

Route::get('/pos', 'POSController@show')->name('pos');
Route::get('/pos/add', 'POSController@store')->name('pos');
Route::get('/pos/get-invoice-no', 'POSController@getInvoiceNo')->name('pos');
Route::get('/pos/daily', 'POSController@printTotalInvoiceDaily')->name('pos');
Route::delete('/pos/delete/{id}', 'POSController@destroy')->name('pos.delete');

Route::get('/order', 'OrderController@show');
Route::get('/order/update', 'OrderController@update');
Route::get('/order/print-invoice', 'OrderController@printInvoice')->name('order.print-invoice');
Route::get('/order/print-daily-invoice', 'OrderController@printDailyInvoice')->name('order.print-daily-invoice');
Route::get('/order/get-invoice-data', 'OrderController@getInvoiceData')->name('order.get-invoice-data');
Route::get('/order/get-daily-invoice-data', 'OrderController@getDailyInvoiceData')->name('order.get-daily-invoice-data');

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

// Language Switch Route
Route::get('locale/{lang}', 'LanguageController@switchLang')->name('locale.switch');

Route::get('/change/delete', 'ChangeController@destroy');
Route::post('/change/update', 'ChangeController@update');

Route::get('/expense', 'ExpenseController@show');
Route::get('/expense/add', 'ExpenseController@store');
Route::get('/expense/delete', 'ExpenseController@destroy');
Route::post('/expense/update', 'ExpenseController@update');
Route::get('/expense/filter', 'ExpenseController@showCustomExpense');
Route::get('/expense/items', 'ExpenseController@getUniqueExpenseItems');

// Expense Item Routes
Route::get('/expense-item', 'ExpenseItemController@index');
Route::post('/expense-item/add', 'ExpenseItemController@store');
Route::post('/expense-item/update/{id}', 'ExpenseItemController@update');
Route::get('/expense-item/delete/{id}', 'ExpenseItemController@destroy');
Route::get('/expense-item/get-all', 'ExpenseItemController@getAll');

Route::get('/delivery', 'DeliveryController@show');
Route::post('/delivery/add', 'DeliveryController@store');
Route::get('/delivery/delete', 'DeliveryController@destroy');
Route::post('/delivery/update', 'DeliveryController@update');


Route::get('/user', 'UserController@show');
Route::post('/user/add', 'UserController@store');
Route::get('/user/delete', 'UserController@destroy');
Route::post('/user/update', 'UserController@update');
Route::post('/user/reset-lockout', 'UserController@resetLockout');

Route::get('/product-income', 'ProductIncomeController@show');

Route::get('/setting', 'SettingController@show');
Route::post('/setting/update', 'SettingController@update');

Route::get('/product-log', 'ProductLogController@show');
Route::get('/product-log/filter', 'ProductLogController@showCustomProductLog');

Route::get('/exchange-rate', 'SettingController@show');
Route::post('/exchange-rate/update', 'SettingController@update');

// Clock In/Out Routes
Route::get('/clock-in-out', 'ClockInOutController@index')->name('clockinout.index');
Route::post('/clock-in', 'ClockInOutController@clockIn')->name('clockinout.clockin');
Route::post('/clock-out', 'ClockInOutController@clockOut')->name('clockinout.clockout');
Route::get('/clock-status', 'ClockInOutController@getStatus')->name('clockinout.status');

// Attendance Routes (Barcode Scanner)
Route::get('/attendance', 'ClockScanController@index')->name('attendance.index');
Route::post('/attendance/process', 'ClockScanController@processScan')->name('attendance.process');
Route::get('/attendance/today', 'ClockScanController@getTodayRecords')->name('attendance.today');

// Clock Report Routes
Route::get('/clock-report', 'ClockReportController@index')->name('clockreport.index');
Route::get('/clock-report/export', 'ClockReportController@export')->name('clockreport.export');
