<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\POS;
use App\TPOS;
use App\Change;
use App\Setting;
use App\Expense;
use Carbon\Carbon;
use Hamcrest\Core\Set;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    // Constants for time formats
    const START_TIME_FORMAT = ' 00:00:00';
    const END_TIME_FORMAT = ' 23:59:59';
    
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show()
    {
        $start_date = Carbon::now()->format('Y-m-d') . self::START_TIME_FORMAT;
        $end_date = Carbon::now()->format('Y-m-d') . self::END_TIME_FORMAT;

        if (auth()->user()->isAdmin()) {
            $orders = TPOS::whereBetween('created_at', [$start_date, $end_date])
                            ->get();
        } else {
            $orders = POS::whereBetween('created_at', [$start_date, $end_date])
                            ->get();
        }
        $data = $this->generateIncomeData($orders);
        return view('sale', [
            'data' => $data,
            'date' => Carbon::now()->format('Y-m-d')
        ]);
    }

    public function cusomterIncomeReport(Request $request)
    {
        $start_date = date($request->date) . self::START_TIME_FORMAT;
        $end_date = date($request->date) . self::END_TIME_FORMAT;


        if (auth()->user()->isAdmin()) {
            $orders = TPOS::whereBetween('created_at', [$start_date, $end_date])
                            ->get();
        } else {
            $orders = POS::whereBetween('created_at', [$start_date, $end_date])
                            ->get();
        }
        $data = $this->generateIncomeData($orders, $request->date);

        
        return view('sale', [
            'data' => $data,
            'date' => $request->date
        ]);
    }

    public function showInvoice()
    {
        $start_date = Carbon::now()->format('Y-m-d') . self::START_TIME_FORMAT;
        $end_date = Carbon::now()->format('Y-m-d') . self::END_TIME_FORMAT;
        
        if (auth()->user()->isAdmin()) {
            // For admin users, get orders from POS table and unique orders from TPOS table
            $pos_orders = POS::whereBetween('created_at', [$start_date, $end_date])->get();
            
            // Get order_no values from POS table to exclude from TPOS query
            $pos_order_numbers = $pos_orders->pluck('order_no')->toArray();
            
            // Get TPOS orders that don't exist in POS table
            $tpos_orders = TPOS::whereBetween('created_at', [$start_date, $end_date])
                ->whereNotIn('order_no', $pos_order_numbers)
                ->get();
            
            // Merge the collections and sort by created_at
            $orders = $pos_orders->concat($tpos_orders)->sortByDesc('created_at')->values();
        } else {
            $orders = POS::whereBetween('created_at', [$start_date, $end_date])->orderBy('created_at', 'DESC')->get();
        }
        
        return view('invoice', [
            'orders' => $orders,
            'date' => Carbon::now()->format('Y-m-d')
        ]);
    }

    public function showCustomInvoice(Request $request)
    {
        $start_date = date($request->date) . self::START_TIME_FORMAT;
        $end_date = date($request->date) . self::END_TIME_FORMAT;

        if (auth()->user()->isAdmin()) {
            // For admin users, get orders from POS table and unique orders from TPOS table
            $pos_orders = POS::whereBetween('created_at', [$start_date, $end_date])->get();
            
            // Get order_no values from POS table to exclude from TPOS query
            $pos_order_numbers = $pos_orders->pluck('order_no')->toArray();
            
            // Get TPOS orders that don't exist in POS table
            $tpos_orders = TPOS::whereBetween('created_at', [$start_date, $end_date])
                ->whereNotIn('order_no', $pos_order_numbers)
                ->get();
            
            // Merge the collections and sort by created_at
            $orders = $pos_orders->concat($tpos_orders)->sortByDesc('created_at')->values();
        } else {
            $orders = POS::whereBetween('created_at', [$start_date, $end_date])->orderBy('created_at', 'DESC')->get();
        }

        return view('invoice', [
            'orders' => $orders,
            'date' => $request->date
        ]);
    }

    private function generateIncomeData($orders, $date = null)
    {
        $items = [];
        $arrange_items = [];
        $invoice = [];
        $total = 0;

        foreach ($orders as $result) {
            // Get discount for this order
            $discount_percentage = 0;
            if (isset($result->additional_info) && isset($result->additional_info['total_discount'])) {
                $discount_percentage = floatval($result->additional_info['total_discount']);
            }

            // Check if items exists and is not null
            if (!$result->items) {
                continue;
            }

            foreach ($result->items as $item) {
                $item_total = floatval(str_replace("$", "", trim($item['total'])));
                
                // Apply discount if this order has one
                if ($discount_percentage > 0) {
                    $discount_amount = $item_total * ($discount_percentage / 100);
                    $item_total = $item_total - $discount_amount;
                }
                
                $data = [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) str_replace("Can", "", trim($item['quantity'])),
                    'total' => $item_total
                ];
                array_push($items, $data);
            }
        }

        foreach ($items as $item) {
            $index = $this->findExistingValueInArray($arrange_items, $item['product_name']);

            if ($index) {
                $arrange_items[$index - 1]['quantity'] += $item['quantity'];
                $arrange_items[$index - 1]['total'] = floatval($arrange_items[$index - 1]['total']) + floatval($item['total']);
            } else {
                array_push($arrange_items, $item);
            }
        }

        // Calculate total
        foreach ($arrange_items as $item) {
            $total += floatval($item['total']);
        }

        $change = Change::where('date', empty($date) ? Carbon::now()->format('Y-m-d') : $date)->first();
        if (empty($change)) {
            $change_in_riel = 0;
            $change_in_usd = 0;
            $total_change = 0;
        } else {
            $change_in_riel = $this->exchangeRielToUSD($change->riel);
            $change_in_usd = $change->usd;
            $total_change = $change_in_riel + $change_in_usd;
        }

        $expenses = Expense::where('date', empty($date) ? Carbon::now()->format('Y-m-d') : $date)->first();
        $total_expense = $this->getTotalExpense($expenses);

        // Prepare invoice items
        foreach ($arrange_items as $item) {
            $data = [
                'product_name' => $item['product_name'],
                'quantity' => strval($item['quantity']),
                'total' => '$' . number_format($item['total'], 2, '.', '')
            ];
            array_push($invoice, $data);
        }

        $total = $total + $total_change - $total_expense;

        // Get exchange rate from settings
        $setting = Setting::first();
        $exchange_rate = $setting ? floatval($setting->exchange_rate) : 4100; // Default to 4100 if not set

        if (auth()->user()->isAdmin()) {
            $payment_type_income = $this->generateIncomeDataFromTPosForPaymentType($date, $total_change, $total_expense);
        } else {
            $payment_type_income = $this->generateIncomeDataForPaymentType($date, $total_change, $total_expense);
        }

        return [
            'items' => $invoice,
            'payment_type_income' => $payment_type_income,
            'total' => number_format($total, 2, '.', ''),
            'total_in_riel' => number_format($total * $exchange_rate, 0, '.', ',')
        ];
    }

    private function generateIncomeDataForPaymentType($date, $total_change, $total_expense)
{
    $start_date = empty($date) ? Carbon::now()->format('Y-m-d') . ' 00:00:00' : date($date) . ' 00:00:00';
    $end_date = empty($date) ? Carbon::now()->format('Y-m-d') . ' 23:59:59' : date($date) . ' 23:59:59';

    // Get orders with both items and additional_info
    $orders_in_cash = POS::whereBetween('created_at', [$start_date, $end_date])
        ->where('payment_type', 'cash')
        ->get();
    $total_income_in_cash = $this->getTotalAmount($orders_in_cash);
    
    // Only use the new multi-currency tracking for dates from 2025-04-25 onwards
    $new_system_start_date = '2025-04-25';
    $is_new_system = empty($date) ? false : $date >= $new_system_start_date;
    
    if ($is_new_system) {
        // For future dates - use the new multi-currency tracking
        $orders_in_cash_details = POS::whereBetween('created_at', [$start_date, $end_date])
            ->where('payment_type', 'cash')
            ->get(['received_in_riel', 'change_in_riel', 'received_in_usd', 'change_in_usd']);
            
        $total_received_in_riel = 0;
        $total_received_in_usd = 0;
        foreach ($orders_in_cash_details as $order) {
            // Calculate net Riel amount - only subtract change if there's a received amount
            $received_riel = isset($order->received_in_riel) ? floatval($order->received_in_riel) : 0;
            if ($received_riel > 0) {
                $change_riel = isset($order->change_in_riel) ? floatval($order->change_in_riel) : 0;
                $total_received_in_riel += ($received_riel - $change_riel);
            }
            
            // Calculate net USD amount
            $received_usd = isset($order->received_in_usd) ? floatval($order->received_in_usd) : 0;
            $change_usd = isset($order->change_in_usd) ? floatval($order->change_in_usd) : 0;
            $total_received_in_usd += ($received_usd - $change_usd);
        }
    } else {
        // For past dates and today - use the original logic (cash value for USD and calculate Riel)
        $total_received_in_usd = $total_income_in_cash;
        
        // Get exchange rate from settings
        $setting = Setting::first();
        $exchange_rate = $setting ? floatval($setting->exchange_rate) : 4100; // Default to 4100 if not set
        
        // Calculate Riel amount based on USD amount and exchange rate
        $total_received_in_riel = $total_income_in_cash * $exchange_rate;
    }

        $orders_in_aba = POS::whereBetween('created_at', [$start_date, $end_date])
            ->where('payment_type', 'aba')
            ->get();
        $total_income_in_aba = $this->getTotalAmount($orders_in_aba);

        $orders_in_acleda = POS::whereBetween('created_at', [$start_date, $end_date])
            ->where('payment_type', 'acleda')
            ->get();
        $total_income_in_acleda = $this->getTotalAmount($orders_in_acleda);

        $orders_in_delivery = POS::whereBetween('created_at', [$start_date, $end_date])
            ->where('payment_type', 'delivery')
            ->get();
        $total_income_in_delivery = $this->getTotalAmount($orders_in_delivery);

        // Handle custom split payments
        $custom_split_orders = POS::whereBetween('created_at', [$start_date, $end_date])
            ->where('payment_type', 'custom')
            ->get();
            
        foreach ($custom_split_orders as $order) {
            if (isset($order->cash_amount)) {
                $total_income_in_cash += floatval($order->cash_amount);
            }
            if (isset($order->aba_amount)) {
                $total_income_in_aba += floatval($order->aba_amount);
            }
        }

        $total_income_in_cash = $total_income_in_cash + $total_change - $total_expense;

    // Only process custom split payments for received amounts if using the new system
    if ($is_new_system) {
        // Add received amounts from custom split payments and subtract change
        foreach ($custom_split_orders as $order) {
            // Handle Riel amounts - only subtract change if there's a received amount
            if (isset($order->received_in_riel)) {
                $received_riel = floatval($order->received_in_riel);
                if ($received_riel > 0) {
                    $change_riel = isset($order->change_in_riel) ? floatval($order->change_in_riel) : 0;
                    $total_received_in_riel += ($received_riel - $change_riel);
                }
            }
            
            // Handle USD amounts
            if (isset($order->received_in_usd)) {
                $received_usd = floatval($order->received_in_usd);
                $change_usd = isset($order->change_in_usd) ? floatval($order->change_in_usd) : 0;
                $total_received_in_usd += ($received_usd - $change_usd);
            }
        }
    }
    
    return [
        'cash' => $total_income_in_cash,
        'cash_in_riel' => $total_received_in_riel,
        'cash_in_usd' => $total_received_in_usd,
        'aba' => $total_income_in_aba,
        'acleda' => $total_income_in_acleda,
        'custom' => 0,
        'delivery' => $total_income_in_delivery,
        'total_expense' => $total_expense,
        'total_change' => $total_change
    ];
    }

    private function generateIncomeDataFromTPosForPaymentType($date, $total_change, $total_expense)
{
    $start_date = empty($date) ? Carbon::now()->format('Y-m-d') . ' 00:00:00' : date($date) . ' 00:00:00';
    $end_date = empty($date) ? Carbon::now()->format('Y-m-d') . ' 23:59:59' : date($date) . ' 23:59:59';

    $orders_in_cash = TPOS::whereBetween('created_at', [$start_date, $end_date])->where('payment_type', 'cash')->get();
    $total_income_in_cash =  $this->getTotalAmount($orders_in_cash);
    
    // Only use the new multi-currency tracking for dates from 2025-04-25 onwards
    $new_system_start_date = '2025-04-25';
    $is_new_system = empty($date) ? false : $date >= $new_system_start_date;
    
    if ($is_new_system) {
        // For future dates - use the new multi-currency tracking
        $orders_in_cash_details = TPOS::whereBetween('created_at', [$start_date, $end_date])
            ->where('payment_type', 'cash')
            ->get(['received_in_riel', 'change_in_riel', 'received_in_usd', 'change_in_usd']);
            
        $total_received_in_riel = 0;
        $total_received_in_usd = 0;
        foreach ($orders_in_cash_details as $order) {
            // Calculate net Riel amount - only subtract change if there's a received amount
            $received_riel = isset($order->received_in_riel) ? floatval($order->received_in_riel) : 0;
            if ($received_riel > 0) {
                $change_riel = isset($order->change_in_riel) ? floatval($order->change_in_riel) : 0;
                $total_received_in_riel += ($received_riel - $change_riel);
            }
            
            // Calculate net USD amount
            $received_usd = isset($order->received_in_usd) ? floatval($order->received_in_usd) : 0;
            $change_usd = isset($order->change_in_usd) ? floatval($order->change_in_usd) : 0;
            $total_received_in_usd += ($received_usd - $change_usd);
        }
    } else {
        // For past dates and today - use the original logic (cash value for USD and calculate Riel)
        $total_received_in_usd = $total_income_in_cash;
        
        // Get exchange rate from settings
        $setting = Setting::first();
        $exchange_rate = $setting ? floatval($setting->exchange_rate) : 4100; // Default to 4100 if not set
        
        // Calculate Riel amount based on USD amount and exchange rate
        $total_received_in_riel = $total_income_in_cash * $exchange_rate;
    }

        $orders_in_aba = TPOS::whereBetween('created_at', [$start_date, $end_date])->where('payment_type', 'aba')->get();
        $total_income_in_aba = $this->getTotalAmount($orders_in_aba);

        $orders_in_acleda = TPOS::whereBetween('created_at', [$start_date, $end_date])->where('payment_type', 'acleda')->get();
        $total_income_in_acleda = $this->getTotalAmount($orders_in_acleda);

        $orders_in_delivery = TPOS::whereBetween('created_at', [$start_date, $end_date])->where('payment_type', 'delivery')->get();
        $total_income_in_delivery = $this->getTotalAmount($orders_in_delivery);

        // Handle custom split payments
        $custom_split_orders = TPOS::whereBetween('created_at', [$start_date, $end_date])
            ->where('payment_type', 'custom')
            ->get();
            
        foreach ($custom_split_orders as $order) {
            if (isset($order->cash_amount)) {
                $total_income_in_cash += floatval($order->cash_amount);
            }
            if (isset($order->aba_amount)) {
                $total_income_in_aba += floatval($order->aba_amount);
            }
        }

        $total_income_in_cash = $total_income_in_cash + $total_change - $total_expense;

    // Only process custom split payments for received amounts if using the new system
    if ($is_new_system) {
        // Add received amounts from custom split payments and subtract change
        foreach ($custom_split_orders as $order) {
            // Handle Riel amounts - only subtract change if there's a received amount
            if (isset($order->received_in_riel)) {
                $received_riel = floatval($order->received_in_riel);
                if ($received_riel > 0) {
                    $change_riel = isset($order->change_in_riel) ? floatval($order->change_in_riel) : 0;
                    $total_received_in_riel += ($received_riel - $change_riel);
                }
            }
            
            // Handle USD amounts
            if (isset($order->received_in_usd)) {
                $received_usd = floatval($order->received_in_usd);
                $change_usd = isset($order->change_in_usd) ? floatval($order->change_in_usd) : 0;
                $total_received_in_usd += ($received_usd - $change_usd);
            }
        }
    }
    
    return [
        'cash' => $total_income_in_cash,
        'cash_in_riel' => $total_received_in_riel,
        'cash_in_usd' => $total_received_in_usd,
        'aba' => $total_income_in_aba,
        'acleda' => $total_income_in_acleda,
        'custom' => 0,
        'delivery' => $total_income_in_delivery,
        'total_expense' => $total_expense,
        'total_change' => $total_change
    ];
    }

    private function getTotalAmount($orders)
    {
        if (count($orders) === 0) {
            return 0;
        }

        $items = [];
        $arrange_items = [];
        $total = 0;

        foreach ($orders as $result) {
            // Get discount for this order
            $discount_percentage = 0;
            if (isset($result->additional_info) && isset($result->additional_info['total_discount'])) {
                $discount_percentage = floatval($result->additional_info['total_discount']);
            }

            // Check if items exists and is not null
            if (!$result->items) {
                continue;
            }

            foreach ($result->items as $item) {
                $item_total = floatval(str_replace("$", "", trim($item['total'])));
                
                // Apply discount if this order has one
                if ($discount_percentage > 0) {
                    $discount_amount = $item_total * ($discount_percentage / 100);
                    $item_total = $item_total - $discount_amount;
                }
                
                $data = [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) str_replace("Can", "", trim($item['quantity'])),
                    'total' => $item_total
                ];
                array_push($items, $data);
            }
        }

        foreach ($items as $item) {
            $index = $this->findExistingValueInArray($arrange_items, $item['product_name']);

            if ($index) {
                $arrange_items[$index - 1]['quantity'] += $item['quantity'];
                $arrange_items[$index - 1]['total'] = floatval($arrange_items[$index - 1]['total']) + floatval($item['total']);
            } else {
                array_push($arrange_items, $item);
            }
        }

        // Calculate final total
        foreach ($arrange_items as $item) {
            $total += floatval($item['total']);
        }

        return number_format($total, 2, '.', '');
    }

    private function findExistingValueInArray($arrays, $value)
    {
        foreach ($arrays as $key => $array) {
            if ($array['product_name'] === $value) {
                return $key + 1;
            }
        }

        return 0;
    }

    private function exchangeRielToUSD($riel)
    {
        $exchange_rate = Setting::first()->exchange_rate;
        return number_format($riel / $exchange_rate, 2);
    }

    private function getTotalExpense($expenses)
    {

        if (empty($expenses)) {
            return 0;
        }

        $total = 0;

        foreach ($expenses->items as $item) {
            $total += (float) $item['cost'];
        }
        return $total;
    }

    /**
     * Update the payment type for an invoice
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentType(Request $request)
    {
        try {
            $id = $request->input('id');
            $paymentType = $request->input('payment_type');
            $deliveryId = $request->input('delivery_id');
            
            // Determine which model to use based on user role
            $model = auth()->user()->isAdmin() ? TPOS::class : POS::class;
            
            $invoice = $model::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ]);
            }
            
            // Update the payment type
            $invoice->payment_type = $paymentType;
            
            // Update delivery_id if payment type is delivery
            if ($paymentType === 'delivery') {
                $invoice->delivery_id = $deliveryId ?: null;
                
                // Update additional_info if needed
                if ($invoice->additional_info && $deliveryId !== 'later' && $deliveryId !== null) {
                    // Check if additional_info is already an array or still a JSON string
                    $additionalInfo = is_array($invoice->additional_info) ? 
                        $invoice->additional_info : 
                        json_decode($invoice->additional_info, true);
                    
                    if (is_array($additionalInfo) && isset($additionalInfo['total'])) {
                        $total = (float) $additionalInfo['total'];
                        
                        // Check if order total is less than $50 and add delivery fee
                        if ($total < 50.00) {
                            // Get the delivery fee from the Delivery model
                            $delivery = \App\Delivery::find($deliveryId);
                            
                            if ($delivery) {
                                $deliveryFee = (float) $delivery->cost;
                                
                                // Check if delivery fee was already added
                                // We'll assume if there's a significant difference between the total and the sum of items,
                                // then a delivery fee was already included
                                $itemsTotal = 0;
                                // Check if items is already an array or still a JSON string
                                $items = is_array($invoice->items) ? 
                                    $invoice->items : 
                                    json_decode($invoice->items, true);
                                
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        if (isset($item['total'])) {
                                            $itemTotal = str_replace('$', '', $item['total']);
                                            $itemTotal = trim($itemTotal);
                                            $itemsTotal += (float) $itemTotal;
                                        }
                                    }
                                }
                                
                                // Calculate the base total (without any delivery fee)
                                $baseTotal = $itemsTotal;
                                
                                // Calculate the new total with the correct delivery fee
                                $newTotal = $baseTotal + $deliveryFee;
                                $additionalInfo['total'] = number_format($newTotal, 2, '.', '');
                                
                                // Update total_riel with the new total if it exists
                                if (isset($additionalInfo['total_riel'])) {
                                    $exchangeRate = 4100; // Default exchange rate
                                    $newTotalRiel = $newTotal * $exchangeRate;
                                    $additionalInfo['total_riel'] = number_format($newTotalRiel, 0, '.', ',');
                                }
                                
                                // No need to json_encode if the model will handle it via casts
                                $invoice->additional_info = $additionalInfo;
                            }
                        }
                    }
                }
                
                // If changing from delivery to another payment type, we might need to remove the delivery fee
                // This would require similar logic to the above, but removing the fee instead
            }
            
            $invoice->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment type updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get the delivery ID for an invoice
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeliveryId(Request $request)
    {
        try {
            $id = $request->input('id');
            
            // Determine which model to use based on user role
            $model = auth()->user()->isAdmin() ? TPOS::class : POS::class;
            
            $invoice = $model::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'delivery_id' => $invoice->delivery_id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }
}
