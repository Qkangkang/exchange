<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnCodeImageToMinaInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mina_infos', function (Blueprint $table) {
            $table->string('mina_code_image')->default("")->comment('小程序二维码');
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
