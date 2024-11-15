<?php

use App\Models\International;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class CreateInternationalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('internationals', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->string('sub');
            $table->timestamps();
        });

        // Données à insérer
        $international = [
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'DZ', 'sub' => '213'], // Algérie
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'BJ', 'sub' => '229'], // Bénin
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'BF', 'sub' => '226'], // Burkina Faso
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'BI', 'sub' => '257'], // Burundi
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'CM', 'sub' => '237'], // Cameroun
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'CV', 'sub' => '238'], // Cap-Vert
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'KM', 'sub' => '269'], // Comores
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'CG', 'sub' => '242'], // Congo (Brazzaville)
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'CD', 'sub' => '243'], // Congo (Kinshasa)
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'CI', 'sub' => '225'], // Côte d'Ivoire
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'DJ', 'sub' => '253'], // Djibouti
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'GA', 'sub' => '241'], // Gabon
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'GN', 'sub' => '224'], // Guinée
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'GW', 'sub' => '245'], // Guinée-Bissau
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'GQ', 'sub' => '240'], // Guinée équatoriale
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'ML', 'sub' => '223'], // Mali
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'MA', 'sub' => '212'], // Maroc
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'MR', 'sub' => '222'], // Mauritanie
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'MU', 'sub' => '230'], // Maurice
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'NE', 'sub' => '227'], // Niger
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'CF', 'sub' => '236'], // République Centrafricaine
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'RW', 'sub' => '250'], // Rwanda
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'ST', 'sub' => '239'], // Sao Tomé-et-Principe
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'SN', 'sub' => '221'], // Sénégal
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'SC', 'sub' => '248'], // Seychelles
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'TD', 'sub' => '235'], // Tchad
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'TG', 'sub' => '228'], // Togo
            ['created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'country' => 'TN', 'sub' => '216'], // Tunisie
        ];

        // Insérer les données
        International::insert($international);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('internationals');
    }
}
