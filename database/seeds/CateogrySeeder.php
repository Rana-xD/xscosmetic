<?php

use Illuminate\Database\Seeder;
use App\Category;
use Carbon\Carbon;


class CateogrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $items = Category::all();

        foreach($items as $item){
            $item->created_at = Carbon::now();
            $item->updated_at = Carbon::now();
            $item->save();
        }

        // Category::insert($data);

    }
}
