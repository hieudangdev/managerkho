<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDurationToTimeSkipInTvcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tvcs', function (Blueprint $table) {
            $table->renameColumn('duration', 'time_skip');
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
            $table->renameColumn('time_skip', 'duration');
        });
    }
}
