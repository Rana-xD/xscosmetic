<?php

use Illuminate\Database\Seeder;
use App\Product;
use App\ProductIncome;

use Carbon\Carbon;

class ProductIncomeSeeder extends Seeder
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
            // $init_data = [
            //     'product_id' => $item->id,
            //     'unit_id' => $item->unit_id,
            //     'product_name' => $item->name,
            // ];
    
            // ProductIncome::create($init_data);

            $record = new ProductIncome();
            $record->product_id = $item->id;
            $record->unit_id = $item->unit_id;
            $record->product_name = $item->name;
            $record->save();
            // print $item->unit_id.'\n';
        }
    }
}
