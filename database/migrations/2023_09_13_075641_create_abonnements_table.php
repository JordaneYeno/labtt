<?php

use App\Models\Abonnement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbonnementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // if (Schema::hasTable('abonnements')) { Schema::dropIfExists('abonnements'); }

        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->string('entreprese_name')->nullable();
            $table->string('entreprese_contact')->nullable();
            $table->string('entreprese_localisation')->nullable();
            $table->string('entreprese_ville')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('sms')->nullable();
            $table->string('email')->nullable();
            $table->integer('whatsapp_status')->default(0);
            $table->integer('sms_status')->default(0);
            $table->integer('email_status')->default(0);
            $table->integer('solde')->default(0);
            $table->integer('status')->default(0);
            $table->string('logo')->nullable();
            $table->string('wa_device_secret')->nullable();
            $table->string('cs_color')->default('#252f3d');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            $table->timestamps();
        });

        
        $abonnements = [
            [
                'user_id' => 2,
                'solde' => 20000000,
                'sms' => 'default',
                'entreprese_name' => 'BAKOAI',
                'entreprese_contact' => null,
                'entreprese_localisation' => null,
                'entreprese_ville' => null,
                'email' => 'noreply@pvitservice.com',
                'whatsapp' => '077438600',
                'sms_status' => 3,
                'email_status' => 3,
                'whatsapp_status' => 3,
                'wa_device_secret' => '6683b7347a25989b6a55fbd0',
                'cs_color' => '#252f3d',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
           ], 
           [
                'user_id' => 3,
                'solde' => 200,
                'sms' => 'FastBox',
                'entreprese_name' => 'FASTBOX',
                'entreprese_contact' => null,
                'entreprese_localisation' => null,
                'entreprese_ville' => null,
                'email' => 'contact@fastboxlivraison.com',
                'whatsapp' => '999999999',
                'sms_status' => 3,
                'email_status' => 3,
                'whatsapp_status' => 3,
                'wa_device_secret' => null,
                'cs_color' => '#252f3d',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], 
            [
                 'user_id' => 4,
                 'solde' => 12,
                 'sms' => null,
                 'entreprese_name' => null,
                 'entreprese_contact' => null,
                 'entreprese_localisation' => null,
                 'entreprese_ville' => null,
                 'email' => null,
                 'whatsapp' => null,
                 'sms_status' => 0,
                 'email_status' => 0,
                 'whatsapp_status' => 0,
                 'wa_device_secret' => null,
                 'cs_color' => '#252f3d',
                 'status' => 0,
                 'created_at' => now(),
                 'updated_at' => now(),
            ],   
        ];
       
        Abonnement::insert($abonnements);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('abonnements');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
