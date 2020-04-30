<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWebHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_history', function (Blueprint $table) {

          $table->bigIncrements('id');

          $table->smallInteger('type');
          $table->string('session_id')->nullable();

          $table->string('path', 2000)->nullable();
          $table->string('referer')->nullable();
          $table->text('query')->nullable();

          $table->string('remote_ip')->nullable();
          $table->text('user_agent')->nullable();

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
        Schema::dropIfExists('web_history');
    }
}
