<?php

use Illuminate\Database\Seeder;

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
            'username' => 'admin',
            'role' => 'ADMIN',
            'password' => Hash::make('admin'),
        ]);

        DB::table('categories')->insert([
            'name' => 'Face Skin',
        ]);

        DB::table('categories')->insert([
            'name' => 'Shampoo',
        ]);

        DB::table('units')->insert([
            'name' => 'Can',
        ]);
    }
}
