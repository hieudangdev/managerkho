<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStatusToStringInTvcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tvcs', function (Blueprint $table) {
            // Thay đổi kiểu dữ liệu của trường 'status' từ enum thành string
            $table->string('status')->default('wait')->change();
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
            // Quay lại kiểu enum cũ nếu cần
            $table->text('status')->default('wait')->change();
        });
    }
}
