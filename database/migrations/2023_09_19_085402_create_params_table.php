<?php

use App\Models\Param;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('params', function (Blueprint $table) {
            $table->id();
            $table->text('secret');
            $table->string('ref');
            $table->timestamps();
        });


        $params = [
            [
                // 'secret' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.YmhDVXcvd1ExV2F4VXhtYkpLM3BjVE5XQ0dNVUFmMURDdGxLSnIzT2l1amVvUlRoS3R4dmZmdzZsZHVRdFZrL25ZTWI0Q2JyK0tyWG85OFE4eXN6cWZxekI1aVRYU25QcGlZeEhTRkRjTlZhakZBR21lSDlYczgySmsvdGY4dHNTRzh6Y3B4ejN6elBOY01xdVBCcm5nMFRINUJ0dnh5TGNBZ2',
                'secret' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.YmhDVXcvd1ExV2F4VXhtYkpLM3BjVE5XQ0dNVUFmMURDdGxLSnIzT2l1amVvUlRoS3R4dmZmdzZsZHVRdFZrL25ZTWI0Q2JyK0tyWG85OFE4eXN6cWZxekI1aVRYU25QcGlZeEhTRkRjTlZhakZBR21lSDlYczgySmsvdGY4dHNTRzh6Y3B4ejN6elBOY01xdVBCcm5nMFRINUJ0dnh5TGNBZ2krQjlJRUE2cFlCWlJNeVd4ZUZDN0FXMVNFTGQ1OjpQdFlFQnkrSTZSQW5zQVdkem82ZE1nPT0=.w+rf14oZndj772P5OqmJAmYFLEw2uNGGAma4Kg3QFAU=',
                'ref' => 'TOKEN_PVIT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'f4371be2c0661c3afade6888456edcc3e672d73ca6c2939a57fc517b7bd4f7daed4efd5a83a677eb',
                'ref' => 'TOKEN_WASSENGER',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'noreply@pvitservice.com',
                'ref' => 'EMAIL_AWT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'adembigondjo@gmail.com',
                'ref' => 'EMAIL_ADMIN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'TST',
                'ref' => 'AM_MARCHAND',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => '066877349',
                'ref' => 'MC_MARCHAND',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'API_HOBOTTA',
                'ref' => 'AGENT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'http://notifsapp.vercel.app',
                'ref' => 'URL_FRONT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => '200',
                'ref' => 'MINE_PRICE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => '1',
                'ref' => 'WHATSAPP_STATUS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => '1',
                'ref' => 'EMAIL_STATUS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => '1',
                'ref' => 'SMS_STATUS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'storage',
                'ref' => 'ENV_DEPLOY',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'http://localhost:8080',
                'ref' => 'URL_API',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => '6683b7347a25989b6a55fbd0',
                'ref' => 'WA_DEVICE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'secret' => 'sms service',
                'ref' => 'SMS_SENDER',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Param::insert($params);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('params');
    }
}
