<?php

use App\Models\Tarifications;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTarificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tarifications', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->default('level_1');
            $table->integer('prix_sms')->default(100);
            $table->integer('prix_whatsapp')->default(50);
            $table->integer('prix_email')->default(10);
            $table->timestamps();
        });

        Tarifications::create();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('tarifications');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
