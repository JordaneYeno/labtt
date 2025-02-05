<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('role_id')->unique();
            $table->string('name')->unique();
            $table->timestamps();

            $roles = [
                [
                    'role_id' => 0,
                    'name' => 'Client',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'role_id' => 1,
                    'name' => 'Admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'role_id' => 2,
                    'name' => 'SuperAdmin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'role_id' => 4,
                    'name' => 'Monitor',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // [
                //     'role_id' => 3,
                //     'name' => 'Guest',
                //     'created_at' => now(),
                //     'updated_at' => now(),
                // ]
            ];

            // Role::insert($roles);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}
