<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'username' => '@dmin',
            'role' => 'SUPERADMIN',
            'password' => Hash::make('Adm1n@admin.'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // DB::table('users')->insert([
        //     'username' => 'manager',
        //     'role' => 'MANAGER',
        //     'password' => Hash::make('m@nager'),
        //     'created_at' => now(),
        //     'updated_at' => now()
        // ]);

        // DB::table('users')->insert([
        //     'username' => 'staff',
        //     'role' => 'STAFF',
        //     'password' => Hash::make('st@ff'),
        //     'created_at' => now(),
        //     'updated_at' => now()
        // ]);

        // DB::table('units')->insert([
        //     'name' => 'Box',
        //     'created_at' => now(),
        //     'updated_at' => now()
        // ]);

        // $this->call(CateogrySeeder::class);
        // $this->call(ProductSeeder::class);
        // $this->call(ProductIncomeSeeder::class);
    }
}
