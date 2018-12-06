<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMinaCrowdRatiosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mina_crowd_ratios', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mid')->comment("所属小程序id");
            $table->string('ratio_name')->comment("比例名称");
            $table->float('ratio_num')->comment("比例数值");
            $table->integer('ratio_type')->comment("比例类型");
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
        Schema::dropIfExists('mina_crowd_ratios');
    }
}
