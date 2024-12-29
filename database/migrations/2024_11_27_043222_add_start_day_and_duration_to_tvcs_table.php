<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartDayAndDurationToTvcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tvcs', function (Blueprint $table) {
            $table->dateTime('start_day')->nullable()->after('status'); // Thêm cột start_day
            $table->integer('duration')->nullable()->after('start_day'); // Thêm cột duration
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tvcs', function (Blueprint $table) {
            $table->dropColumn(['start_day', 'duration']);
        });
    }
}
