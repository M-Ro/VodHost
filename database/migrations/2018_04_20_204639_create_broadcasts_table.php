<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBroadcastsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->string('description');
            $table->string('state');
            $table->string('filename');
            $table->unsignedInteger('filesize');
            $table->float('length');
            $table->unsignedInteger('views');
            $table->boolean('public');
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
        Schema::dropIfExists('broadcasts');
    }
}
