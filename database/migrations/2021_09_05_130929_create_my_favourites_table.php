<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyFavouritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('my_favourites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger("user_request_id")->index()->nullable();
            $table->foreign("user_request_id")->references("id")->on("user_requests")->onDelete("cascade");
            $table->unsignedBigInteger("user_id")->index()->nullable();
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            \App\Helpers\DbExtender::defaultParams($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('my_favourites');
    }
}
