<?php

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->truncate();
        DB::table('users')->insert([
            'name'     => 'manager',
            'email'    => 'manage@gmail.com',
            'password' => Hash::make('admin123'),
            'is_admin' => 1
        ]);
    }
}
