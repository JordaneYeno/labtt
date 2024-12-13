<?php

use App\Models\Tarifications;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $table->string('nom')->default('grille'); 
            $table->decimal('prix_sms', 8, 2)->default(0.00); // 'prix_sms'
            $table->decimal('prix_whatsapp', 8, 2)->default(0.00); // 'prix_whatsapp'
            $table->decimal('prix_email', 8, 2)->default(0.00); // 'prix_email'
            $table->timestamps();
        });

        $grilles = [
            [
                'nom' => 'default',
                'prix_sms' => 50.00,
                'prix_email' => 0.10,
                'prix_whatsapp' => 15.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'media',
                'prix_sms' => 50.00,
                'prix_email' => 0.10,
                'prix_whatsapp' => 20.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],  
            [
                'nom' => 'international',
                'prix_sms' => 100.00,
                'prix_email' => 0.10,
                'prix_whatsapp' => 20.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        
        Tarifications::insert($grilles);
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
