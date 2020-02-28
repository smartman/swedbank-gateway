<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SwedbankRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('swedbank_requests', function ($t) {
            $t->increments('id');
            $t->string('correlation_id')->unique();
            $t->text('request_xml');
            $t->string('tracking_id')->nullable()->unique();
            $t->mediumText('response_xml')->nullable();
            $t->boolean('deleted')->default(false);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('swedbank_requests');
    }
}
