<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Category;

class CategoryController extends Controller {

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $categories = Category::All();
        return view('category.view',[
            'categories' => $categories
        ]);
    }

    public function store(Request $request){
        $data = [
            "name" => $request->name,
        ];

        Category::create($data);

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request){
        $id = (int)$request->id;
        Category::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request){
        $data = [
            "name" => $request->name,
        ];

        $id = $request->id;

        Category::find($id)->update($data);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);        
    }
}