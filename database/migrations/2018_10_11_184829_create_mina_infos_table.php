<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMinaInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mina_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('uid')->comment("拥有者id");
            $table->string('name')->comment("小程序名称");
            $table->string('img')->comment("小程序图片");
            $table->integer('cid')->comment("类目id");
            $table->integer('con_min')->default(0)->comment("导量最小值");
            $table->integer('con_max')->default(0)->comment("导量最大值");
            $table->integer('success_count')->default(0)->comment("成功合作次数");
            $table->string('exc_condition')->default(1)->comment("1为新增UV,2为授权用户,3为点击UV");
            $table->string("mina_remark")->default("")->nullable()->comment("小程序备注");
            $table->string("audit_type")->default(1)->comment("审核状态，0未审核1审核通过2未通过");
            $table->integer('status')->default(0)->comment("冻结状态,0否1是");
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
        Schema::dropIfExists('mina_infos');
    }
}
