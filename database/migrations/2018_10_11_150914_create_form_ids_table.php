<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_ids', function (Blueprint $table) {
            $table->increments('id');
            $table->string("form_id")->comment("formid");
            $table->integer("uid")->comment("用户id");
            $table->integer("is_use")->default(0)->comment("是否使用");
            $table->integer("express")->comment("有效期");
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
        Schema::dropIfExists('form_ids');
    }
}
