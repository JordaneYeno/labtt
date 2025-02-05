<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AddClientMonitor extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        User::insert([
            [
                'name' => 'bornesads',
                'email' => 'bornesads@ff.pro',
                'password' => bcrypt('$123456'),
                'role_id' => 4,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
           ]
        ]);
    }
}
