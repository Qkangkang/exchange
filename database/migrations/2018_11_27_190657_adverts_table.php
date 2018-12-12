<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdvertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adverts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment("广告名称");
            $table->string('mark')->default('')->comment("描述");
            $table->string('image')->default('')->comment("图片地址");
            $table->string('app_id')->default('')->comment("appId");
            $table->string('app_path')->default('')->comment("跳转路径");
            $table->integer('status')->default(0)->comment("上架状态，0为下架，1为上架");
            $table->string('position')->default('personal')->comment("广告位置");
            $table->integer("type")->default(1)->comment("广告类型，1为自定义广告");
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
        Schema::dropIfExists('adverts');
    }
}
