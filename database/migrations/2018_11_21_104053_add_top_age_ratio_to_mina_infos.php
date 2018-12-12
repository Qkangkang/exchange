<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTopAgeRatioToMinaInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('mina_infos', function (Blueprint $table) {
            $table->string('top_age_ratio_name')->default('')->comment('年龄段比例最高区间');
            $table->string('top_sex_ratio_name')->default('')->comment("性别段比例最高区间");
            $table->string('top_mobile_ratio_name')->default('')->comment("机型比例最高区间");
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
        Schema::table('mina_infos', function (Blueprint $table) {
            //
        });
    }
}
