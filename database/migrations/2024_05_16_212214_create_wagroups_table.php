<?php

use App\Models\Wagroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWagroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wagroups', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->integer('total')->length(2)->default(0);
            $table->string('name');
            $table->string('kwid')->nullable();
            $table->string('kid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wagroups');
    }
}
