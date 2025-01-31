<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuthMonitorsCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_monitors_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique(); // Nom d'utilisateur (doit être unique)
            $table->string('password'); // Mot de passe (hashé)
            $table->boolean('is_active')->default(true); // Si le compte est actif
            $table->timestamp('expires_at')->nullable(); // Date d'expiration du compte
            $table->timestamps(); // created_at et updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_monitors_credentials');
    }
}
