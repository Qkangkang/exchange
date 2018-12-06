<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("uid")->comment("用户id");
            $table->string("formid")->comment("formid");
            $table->integer("add_time")->comment("发送时间");
            $table->integer("type");
            $table->integer("status")->default(0)->comment("1成功 2失败");
            $table->string("msg")->nullable()->comment("报错信息");
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
        Schema::dropIfExists('template_messages');
    }
}
