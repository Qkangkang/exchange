<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComplainListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complain_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("uid")->default(0)->comment("投诉人id");
            $table->integer("mina_id")->default(0)->comment("投诉小程序id");
            $table->integer("com_type")->default(1)->comment("投诉类型,1为虚假,2为信息不符,3为广告,4为其他");
            $table->string("complain_remark")->default("")->comment("描述");
            $table->string("img1")->default(0)->comment("图片1");
            $table->string("img2")->default(0)->comment("图片2");
            $table->string("img3")->default(0)->comment("图片3");
            $table->integer("handle_type")->default(1)->comment("受理状态,1为未受理,2为已受理");
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
        Schema::dropIfExists('complain_lists');
    }
}
