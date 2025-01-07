<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHasCustomTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->boolean('has_custom_template')->default(false)->after('cs_color');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropColumn('has_custom_template');
        });
    }
}
