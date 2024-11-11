<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Expense;
use Carbon\Carbon;

class ExpenseController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function show()
    {
        $today = Carbon::now()->format('Y-m-d');
        $expense = Expense::where('date', $today)->first();
        $total = null;
        if (!empty($expense)) {
            $total = $this->getTotalExpense($expense->items);
        }
        return view('expense', [
            'date' => $today,
            'expense' => $expense,
            'total' => $total
        ]);
    }

    public function store(Request $request)
    {

        $today = Carbon::now()->format('Y-m-d');
        $expense = Expense::where('date', $today)->first();
        if (empty($expense)) {
            $data = [
                "items" => $request->items,
                "date" => $today
            ];
            Expense::create($data);
        } else {
            $existingItems = $expense->items;
            $newItems = $request->items;

            $mergedItems = array_merge($existingItems, $newItems);

            $uniqueItems = [];
            foreach ($mergedItems as $item) {
                $found = false;
                foreach ($uniqueItems as &$uniqueItem) {
                    if ($uniqueItem['name'] === $item['name']) {
                        $uniqueItem['cost'] = (float)$uniqueItem['cost'] + (float)$item['cost'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $uniqueItems[] = $item;
                }
            }

            $expense->setAttribute('items', $uniqueItems);
            $expense->save();
        }



        return response()->json([
            'code' => 200
        ]);
    }

    public function destroy(Request $request)
    {
        $id = (int)$request->id;
        Expense::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request)
    {
        $data = [
            "items" => json_decode($request->items, true)
        ];

        $id = $request->id;

        Expense::find($id)->update($data);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function showCustomExpense(Request $request)
    {
        $date = $request->date;
        $expense = Expense::where('date', $date)->first();
        $total = null;
        if (!empty($expense)) {
            $total = $this->getTotalExpense($expense->items);
        }
        return view('expense', [
            'date' => $date,
            'expense' => $expense,
            'total' => $total
        ]);
    }

    private function getTotalExpense($items)
    {
        $total = 0;

        foreach ($items as $item) {
            $total += (float) $item['cost'];
        }
        return $total;
    }
}
