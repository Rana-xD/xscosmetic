<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\POS;
use Carbon\Carbon;

class SaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $start_date = Carbon::now()->format('Y-m-d').' 00:00:00';
        $end_date = Carbon::now()->format('Y-m-d').' 23:59:59';
        $orders = POS::whereBetween('created_at',[$start_date,$end_date])->pluck('items');
        $data = $this->generateIncomeData($orders);
        return view('sale',[
            'data' => $data
        ]);
    }

    public function cusomterIncomeReport(Request $request){
        $start_date = date($request->date).' 00:00:00';
        $end_date = date($request->date).' 23:59:59';

        $orders = POS::whereBetween('created_at',[$start_date,$end_date])->pluck('items');
        $data = $this->generateIncomeData($orders,$request->date);

        return view('sale',[
             'data' => $data,
             'date' => $request->date
        ]);
    }

    public function showInvoice(){
        $orders = POS::orderBy('created_at', 'DESC')->paginate(30);
        return view('invoice',[
            'orders' => $orders
        ]);
    }

    public function showCustomInvoice(Request $request){    
        $start_date = date($request->start_date).' 00:00:00';
        $end_date = empty($request->end_date) ? Carbon::now()->format('Y-m-d').' 23:59:59' :  date($request->end_date).' 23:59:59';

        $orders = POS::whereBetween('created_at',[$start_date,$end_date])->orderBy('created_at', 'DESC')->paginate(30);

        return view('invoice',[
            'orders' => $orders,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);
    }

    private function generateIncomeData($orders, $date = null) {
        $items = [];
        $arrange_items = [];
        $invoice = [];
        $total = 0;
    
        foreach ($orders as $result){
            foreach($result as $item){
                $data = [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) str_replace("Can"," ",$item['quantity']),
                    'total' => (float) str_replace("$"," ",$item['total'])
                ];
                array_push($items,$data);
            }
        }

        foreach($items as $item){
            $index = $this->findExistingValueInArray($arrange_items,$item['product_name']);

            if($index){
                $arrange_items[$index-1]['quantity'] += $item['quantity'];
                $arrange_items[$index-1]['total'] += $item['total'];
            }else{
                array_push($arrange_items,$item);
            }
        }

        foreach($arrange_items as $item){

            $data = [
                'product_name' => $item['product_name'],
                'quantity' => strval($item['quantity']),
                'total' => '$'.$item['total']
            ];
            array_push($invoice,$data);
        }

        foreach($arrange_items as $item){
            $total += $item['total'];
        }

        $payment_type_income = $this->generateIncomeDataForPaymentType($date);
        
        $income_data = [
            'items' => $invoice,
            'payment_type_income' => $payment_type_income,
            'total' => $total
        ];
        return $income_data;
    }

    private function generateIncomeDataForPaymentType($date) {
        $start_date = empty($date) ? Carbon::now()->format('Y-m-d').' 00:00:00' : date($date).' 00:00:00';
        $end_date = empty($date) ? Carbon::now()->format('Y-m-d').' 23:59:59' : date($date).' 23:59:59';

        $orders_in_cash = POS::whereBetween('created_at',[$start_date,$end_date])->where('payment_type','cash')->pluck('items');
        $total_income_in_cash =  $this->getTotalAmount($orders_in_cash);

        $orders_in_aba = POS::whereBetween('created_at',[$start_date,$end_date])->where('payment_type','aba')->pluck('items');
        $total_income_in_aba = $this->getTotalAmount($orders_in_aba);

        $orders_in_acleda = POS::whereBetween('created_at',[$start_date,$end_date])->where('payment_type','acleda')->pluck('items');
        $total_income_in_acleda = $this->getTotalAmount($orders_in_acleda);

        $orders_in_delivery = POS::whereBetween('created_at',[$start_date,$end_date])->where('payment_type','delivery')->pluck('items');
        $total_income_in_delivery = $this->getTotalAmount($orders_in_delivery);

        $payment_type_income = [
            'cash' => $total_income_in_cash,
            'aba' => $total_income_in_aba,
            'acleda' => $total_income_in_acleda,
            'delivery' => $total_income_in_delivery
        ];

        return $payment_type_income;
    }

    private function getTotalAmount($orders){
        if(count($orders) === 0) { 
            return 0;
         }

         $items = [];
         $arrange_items = [];
         $total = 0;

         foreach ($orders as $result){
            foreach($result as $item){
                $data = [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) str_replace("Can"," ",$item['quantity']),
                    'total' => (float) str_replace("$"," ",$item['total'])
                ];
                array_push($items,$data);
            }
        }

        foreach($items as $item){
            $index = $this->findExistingValueInArray($arrange_items,$item['product_name']);

            if($index){
                $arrange_items[$index-1]['quantity'] += $item['quantity'];
                $arrange_items[$index-1]['total'] += $item['total'];
            }else{
                array_push($arrange_items,$item);
            }
        }

        foreach($arrange_items as $item){
            $total += $item['total'];
        }

        return $total;
    }

    private function findExistingValueInArray($arrays, $value){
        foreach($arrays as $key => $array){
            if($array['product_name'] === $value){
                return $key+1;
            }
        }

        return 0;
    }


}
