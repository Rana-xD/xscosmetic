<?php

use Illuminate\Database\Seeder;
use App\Product;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = Product::all();

        foreach($items as $item){
            $item->created_at = Carbon::now();
            $item->updated_at = Carbon::now();
            $item->save();
        }
    }
}
