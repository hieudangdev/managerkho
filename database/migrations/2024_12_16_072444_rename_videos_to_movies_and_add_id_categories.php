<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameVideosToMoviesAndAddIdCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('videos', 'movies');

        Schema::table('movies', function (Blueprint $table) {
            $table->unsignedBigInteger('id_categories')->after('slug')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('id_categories');
        });

        Schema::rename('movies', 'videos');
    }
}
