<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookmakerIdToTvcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tvcs', function (Blueprint $table) {
            $table->unsignedBigInteger('bookmaker_id')->nullable()->after('id'); // Thêm cột bookmaker_id
            $table->foreign('bookmaker_id')->references('id')->on('bookmakers')->onDelete('set null'); // Thiết lập khóa ngoại
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
            $table->dropForeign(['bookmaker_id']); // Xóa khóa ngoại
            $table->dropColumn('bookmaker_id'); // Xóa cột
        });
    }
}
