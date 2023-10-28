<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Admin::insert(
            [
                [
                'fname' => 'Super',
                'lname' => 'Admin',
                'email' => 'admin@kaz.com',
                'password' => bcrypt('12345678'),
                'status' => 1,
                ]
            ]);
    }
}
