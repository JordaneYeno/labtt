<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndTimezoneNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('time_zone')->nullable()->after('has_final_status');
            $table->enum('status', ['pending', 'in_progress', 'sent', 'failed', 'deferred'])->default('pending')->after('delivery_status'); 
 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('time_zone');
            $table->dropColumn('status');
        });
    }
}
