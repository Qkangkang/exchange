<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnAuditTypeToMinaInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mina_infos', function (Blueprint $table) {
            $table->integer('audit_type')->default(1)->comment('审核状态，0为未审核，1为已审核，2为审核不通过');
            $table->string('exc_condition')->default(1)->comment("1为新增UV,2为授权用户,3为点击UV")->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mina_infos', function (Blueprint $table) {
            //
        });
    }
}
