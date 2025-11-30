<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('tbl_custom_tour_requests', function (Blueprint $table) {
        $table->enum('tour_type', ['group', 'private'])
              ->default('group')
              ->after('hotel_level');
    });

    Schema::table('tbl_custom_tours', function (Blueprint $table) {
        $table->enum('tour_type', ['group', 'private'])
              ->default('group')
              ->after('hotel_level');
    });
}

public function down()
{
    Schema::table('tbl_custom_tour_requests', function (Blueprint $table) {
        $table->dropColumn('tour_type');
    });

    Schema::table('tbl_custom_tours', function (Blueprint $table) {
        $table->dropColumn('tour_type');
    });
}

};
