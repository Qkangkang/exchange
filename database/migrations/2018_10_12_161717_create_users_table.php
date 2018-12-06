<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string("nick_name",191)->default("")->comment("昵称");
            $table->string("user_name",191)->default("")->comment("姓名");
            $table->string("avatar",191)->default("")->comment("头像");
            $table->string("wechat",20)->default("")->comment("微信号");
            $table->string("openid",50)->comment("openid")->unique();
            $table->string("phone",13)->default("")->comment("手机号码");
            $table->string("company",25)->default("")->comment("公司名称");
            $table->integer("apply_count")->default(0)->comment("申请次数");
            $table->integer("remain_apply_count")->default(5)->comment("当天剩余申请次数");
            $table->integer("status")->default(0)->comment("是否被封禁,0否1是");
            $table->string("unionid",50)->nullable();
            $table->string("access_token",50)->unique()->comment("用户登录token");
            $table->integer("uid")->default(0)->comment("邀请人id");
            $table->string("session_key")->comment("session_key");
            $table->integer("login_at")->default(0)->comment("最后一次登录时间");
            $table->timestamps();
            $table->softDeletes();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}