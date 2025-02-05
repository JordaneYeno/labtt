<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Role::insert([
            ['role_id' => 0, 'name' => 'Client', 'created_at' => now(), 'updated_at' => now()],
            ['role_id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['role_id' => 2, 'name' => 'SuperAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['role_id' => 4, 'name' => 'Monitor', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
