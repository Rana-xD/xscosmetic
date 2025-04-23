<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ExpenseItem;
use Illuminate\Support\Facades\Auth;

class ExpenseItemController extends Controller
{
    /**
     * Display a listing of expense items.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $expenseItems = ExpenseItem::orderBy('name', 'asc')->get();
        return view('expense-item', compact('expenseItems'));
    }

    /**
     * Store a newly created expense item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
        ]);

        ExpenseItem::create([
            'name' => $request->name,
            'cost' => $request->cost,
        ]);

        return redirect()->back()->with('success', 'Expense item added successfully');
    }

    /**
     * Update the specified expense item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
        ]);

        $expenseItem = ExpenseItem::findOrFail($id);
        $expenseItem->update([
            'name' => $request->name,
            'cost' => $request->cost,
        ]);

        return redirect()->back()->with('success', 'Expense item updated successfully');
    }

    /**
     * Remove the specified expense item.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $expenseItem = ExpenseItem::findOrFail($id);
        $expenseItem->delete();

        return redirect()->back()->with('success', 'Expense item deleted successfully');
    }

    /**
     * Get all expense items as JSON for AJAX requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll()
    {
        $expenseItems = ExpenseItem::orderBy('name', 'asc')->get();
        return response()->json(['items' => $expenseItems]);
    }
}
