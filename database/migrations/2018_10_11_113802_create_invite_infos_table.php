<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInviteInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invite_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("uid")->default(0)->comment("邀请人id");
            $table->integer("b_uid")->default(0)->comment("被邀请人id");
            $table->integer("mina_id")->default(0)->comment("小程序id");
            $table->integer("status")->default(1)->comment("邀请状态,1为邀请中,2为已同意,3为已拒绝");
            $table->integer("agree_type")->default(0)->comment("点赞状态,0为未点赞,1未已点赞");
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
        Schema::dropIfExists('invite_infos');
    }
}
