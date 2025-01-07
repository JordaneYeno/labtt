<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id(); // Identifiant unique
            $table->unsignedBigInteger('user_id'); // Clé étrangère vers clients
            $table->string('name'); 
            $table->boolean('is_default')->default(false); 
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('abonnements')->onDelete('cascade'); // Relation avec clients
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('templates');
    }
}
