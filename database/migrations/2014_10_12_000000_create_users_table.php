<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // if (Schema::hasTable('users')) { Schema::dropIfExists('users'); }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id')->default(0);
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('otp')->nullable();
            $table->string('password');
            $table->boolean('is_notify')->nullable();
            $table->integer('status')->default(0);
            $table->boolean('delete_status')->default(0);
            $table->boolean('is_valid')->nullable();
            $table->string('slug')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('tarification_id')->default(1);
            $table->foreign('tarification_id')
                ->references('id')
                ->on('tarifications')
                ->onDelete('cascade');
            $table->timestamps();
        });


        $users = [
            [
                'name' => 'superadmin',
                'email' => 'superadmin@bakoai.pro',
                'password' => bcrypt('123456'),
                'role_id' => 2,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
           ], 
           [
                'name' => 'pvitservice',
                'email' => 'pvitservice@bakoai.pro',
                'password' => bcrypt('Pvitservice$241'),
                'role_id' => 3,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
           ],  
           [
                'name' => 'fastbox',
                'email' => 'contact@fastboxlivraison.com',
                'password' => bcrypt('Fastbox$241'),
                'role_id' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
           ],  
           [
                'name' => 'teste01',
                'email' => 'teste01@gmail.com',
                'password' => bcrypt('123456'),
                'role_id' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
           ],  
        ];
       
        User::insert($users);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('users');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
