<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->string('ed_reference');
                $table->string('title');
                $table->string('canal')->nullable();
                $table->text('message');
                $table->string('banner')->nullable();
                $table->string('email_awt')->nullable();
                $table->string('code_textopro')->nullable();
                $table->string('slug')->nullable();
                $table->string('expediteur')->nullable();
                $table->dateTime('finish')->nullable();
                $table->dateTime('start')->nullable();
                $table->boolean('status')->default(0);
                $table->boolean('credit')->default(0);
                $table->string('verify')->nullable();
                $table->string('destinataires')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')   
                    ->onDelete('cascade');
                $table->timestamp('date_envoie')->nullable();
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
        Schema::dropIfExists('messages');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
