<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Product;

class MoveCostDataToCostGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:moveCostDataToCostGroup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $cost_group = [$product->cost];
            $product->cost_group = $cost_group;
            $product->save();
        }

        echo "Done";
    }
}
