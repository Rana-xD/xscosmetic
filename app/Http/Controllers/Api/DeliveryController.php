<?php

namespace App\Http\Controllers\Api;

use App\Delivery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Get delivery information by ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $delivery = Delivery::find($id);
        
        if (!$delivery) {
            return response()->json(['error' => 'Delivery not found'], 404);
        }
        
        return response()->json($delivery);
    }
}
