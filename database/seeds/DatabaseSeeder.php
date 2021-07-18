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
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('categories')->insert([
            'name' => 'Face Skin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('categories')->insert([
            'name' => 'Shampoo',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('units')->insert([
            'name' => 'Can',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
