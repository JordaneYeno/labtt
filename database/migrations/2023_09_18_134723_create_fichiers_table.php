<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFichiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('fichiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');
            $table->string('lien');
            $table->string('nom');
            $table->string('extension');
            $table->string('mime_type');            
            $table->integer('taille');
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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('fichiers');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
