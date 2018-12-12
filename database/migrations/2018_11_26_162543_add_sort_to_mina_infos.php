n<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSortToMinaInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mina_infos', function (Blueprint $table) {
            $table->integer('sort')->default(99)->comment('排序(默认排序99,用户合作过的为100)');
            $table->string('label')->default('')->comment('小程序标签id');
            $table->integer('complain_count')->default(0)->comment('投诉次数');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
