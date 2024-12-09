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
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show()
    {
        $start_date = Carbon::now()->format('Y-m-d') . ' 00:00:00';
        $end_date = Carbon::now()->format('Y-m-d') . ' 23:59:59';

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
        $start_date = date($request->date) . ' 00:00:00';
        $end_date = date($request->date) . ' 23:59:59';


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

        $start_date = Carbon::now()->format('Y-m-d') . ' 00:00:00';
        $end_date = Carbon::now()->format('Y-m-d') . ' 23:59:59';
        if (auth()->user()->isAdmin()) {
            $orders = TPOS::whereBetween('created_at', [$start_date, $end_date])->orderBy('created_at', 'DESC')->get();
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
        $start_date = date($request->date) . ' 00:00:00';
        $end_date = date($request->date) . ' 23:59:59';


        if (auth()->user()->isAdmin()) {
            $orders = TPOS::whereBetween('created_at', [$start_date, $end_date])->orderBy('created_at', 'DESC')->get();
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

        if (auth()->user()->isAdmin()) {
            $payment_type_income = $this->generateIncomeDataFromTPosForPaymentType($date, $total_change, $total_expense);
        } else {
            $payment_type_income = $this->generateIncomeDataForPaymentType($date, $total_change, $total_expense);
        }

        $income_data = [
            'items' => $invoice,
            'payment_type_income' => $payment_type_income,
            'total' => number_format($total, 2, '.', '')
        ];
        return $income_data;
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

        $payment_type_income = [
            'cash' => $total_income_in_cash,
            'change' => $total_change,
            'expense' => $total_expense,
            'aba' => $total_income_in_aba,
            'acleda' => $total_income_in_acleda,
            'delivery' => $total_income_in_delivery
        ];

        return $payment_type_income;
    }

    private function generateIncomeDataFromTPosForPaymentType($date, $total_change, $total_expense)
    {
        $start_date = empty($date) ? Carbon::now()->format('Y-m-d') . ' 00:00:00' : date($date) . ' 00:00:00';
        $end_date = empty($date) ? Carbon::now()->format('Y-m-d') . ' 23:59:59' : date($date) . ' 23:59:59';

        $orders_in_cash = TPOS::whereBetween('created_at', [$start_date, $end_date])->where('payment_type', 'cash')->get();
        $total_income_in_cash =  $this->getTotalAmount($orders_in_cash);

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

        $payment_type_income = [
            'cash' => $total_income_in_cash,
            'change' => $total_change,
            'expense' => $total_expense,
            'aba' => $total_income_in_aba,
            'acleda' => $total_income_in_acleda,
            'delivery' => $total_income_in_delivery
        ];

        return $payment_type_income;
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
}
