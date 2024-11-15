<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abonnement_id');
            $table->foreign('abonnement_id')
                ->references('id')
                ->on('abonnements')
                ->onDelete('cascade');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');
            $table->unsignedBigInteger('paiement_id')->nullable();
            $table->foreign('paiement_id')
                ->references('id')
                ->on('paiements')
                ->onDelete('cascade');
            $table->string('type');
            $table->string('montant');
            $table->boolean('status');
            $table->integer('total_sms')->nullable();
            $table->integer('total_whatsapp')->nullable();
            $table->integer('total_email')->nullable();
            $table->string('nouveau_solde');
            $table->uuid('reference')->unique();
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
        Schema::dropIfExists('transactions');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
