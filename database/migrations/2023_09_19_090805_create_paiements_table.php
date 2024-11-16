<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaiementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('interface_id')->nullable();
            $table->string('ref')->nullable();
            $table->string('reference_marchand')->nullable();
            $table->string('num_transaction')->nullable();
            $table->string('operateur')->nullable();
            $table->string('token')->nullable();
            $table->string('agent')->nullable();
            $table->string('message')->nullable();
            $table->string('final_message')->nullable();
            $table->integer('amount')->default(0);
            $table->integer('fees')->default(0);
            $table->integer('type')->nullable();
            $table->string('numero_client', 9)->nullable();
            $table->string('tel_client', 9)->nullable();
            $table->integer('statut')->nullable();
            $table->integer('final_status')->default(0);
            $table->bigInteger('abonnement_id')->unsigned();
            $table->foreign('abonnement_id')
                ->references('id')
                ->on('abonnements');
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
        Schema::dropIfExists('paiements');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
