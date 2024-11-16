<?php

use App\Models\Assistance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssistancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assistances', function (Blueprint $table) {
            $table->id();
            $table->string('agent');
            $table->string('phone')->unique();
            $table->integer('role')->default(0);
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        $agents = [
            [
                'agent' => 'pvit',
                'phone' => '+24104721398',
                'role' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent' => 'sadathe',
                'phone' => '+24107002872',
                'role' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent' => 'jordane',
                'phone' => '+24104907607',
                'role' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent' => 'christophe',
                'phone' => '+24106090270',
                'role' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent' => 'tchang',
                'phone' => '+24104698185',
                'role' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent' => 'chancel',
                'phone' => '+24177432777',
                'role' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent' => 'gedeon',
                'phone' => '+22557727688',
                'role' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent' => 'christian',
                'phone' => '+24107020265',
                'role' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Assistance::insert($agents);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assistances');
    }
}
