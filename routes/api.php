<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\DeliveryController;

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

// Commented out to enable route caching (closures cannot be serialized)
// If you need this route, create a controller method instead
// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Delivery API routes
Route::get('/delivery/{id}', [DeliveryController::class, 'show']);

// Cache webhook routes (for remote system to notify local cache)
Route::post('/cache/clear-products', [\App\Http\Controllers\CacheWebhookController::class, 'clearProducts']);
Route::post('/cache/refresh-products', [\App\Http\Controllers\CacheWebhookController::class, 'refreshProducts']);
