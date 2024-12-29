<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // Tên chiến dịch
            $table->text('description')->nullable();  // Mô tả chiến dịch
            $table->text('text_ads')->nullable();  // Quảng cáo dạng văn bản
            $table->string('link_ads')->nullable();  // Link quảng cáo
            $table->timestamps();
        });

        // Tạo bảng trung gian campaign_domain để lưu các domain liên kết với chiến dịch
        Schema::create('campaign_domain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
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
        Schema::dropIfExists('campaign_domain');
        Schema::dropIfExists('campaigns');
    }
}
