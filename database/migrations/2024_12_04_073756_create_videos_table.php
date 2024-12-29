<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->nullable();
            $table->string('tags')->nullable();
            $table->string('quality')->nullable();

            $table->string('video_path'); // Đường dẫn video MP4
            $table->string('hls_link')->nullable(); // Đường dẫn HLS m3u8

            $table->string('thump')->nullable(); // Thumbnail
            $table->string('poster')->nullable(); // Poster
            $table->string('country')->nullable(); // Quốc gia
            $table->string('genre')->nullable(); // Thể loại
            $table->string('actors')->nullable(); // Diễn viên
            $table->year('year')->nullable(); // Năm
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
        Schema::dropIfExists('videos');
    }
}
