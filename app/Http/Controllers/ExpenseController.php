<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Expense;
use Carbon\Carbon;

class ExpenseController extends Controller {

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function show(){
        $today = Carbon::now()->format('Y-m-d');
        $expense = Expense::where('date',$today)->first();
        $total = null;
        if(!empty($expense)){
          $total = $this->getTotalExpense($expense->items);  
        }
        return view('expense',[
            'expense' => $expense,
            'total' => $total
        ]);
    }

    public function store(Request $request){

        $today = Carbon::now()->format('Y-m-d');
        $expense = Expense::where('date',$today)->first();
        if(empty($expense)){
            $data = [
                "items" => $request->items,
                "date" => $today
            ];
            Expense::create($data);
        }else{
            $expense->setAttribute('items', array_merge($expense->items, $request->items));
            $expense->save();
        }

        

        return response()->json([
            'code' => 200
        ]);
    }

    public function destroy(Request $request){
        $id = (int)$request->id;
        Expense::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request){
        $data = [
            "name" => $request->name,
        ];

        $id = $request->id;

        Expense::find($id)->update($data);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);        
    }

    private function getTotalExpense($items){
        $total = 0;

        foreach($items as $item){
            $total += (float) $item['cost'];
        }
        return $total;
    }

}