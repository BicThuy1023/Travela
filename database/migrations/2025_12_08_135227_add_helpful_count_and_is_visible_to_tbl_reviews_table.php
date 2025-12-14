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
        Schema::table('tbl_reviews', function (Blueprint $table) {
            // Thêm helpful_count nếu chưa có
            if (!Schema::hasColumn('tbl_reviews', 'helpful_count')) {
                $table->integer('helpful_count')->default(0)->after('comment');
            }
            // Thêm is_visible nếu chưa có
            if (!Schema::hasColumn('tbl_reviews', 'is_visible')) {
                $table->boolean('is_visible')->default(1)->after('helpful_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tbl_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_reviews', 'helpful_count')) {
                $table->dropColumn('helpful_count');
            }
            if (Schema::hasColumn('tbl_reviews', 'is_visible')) {
                $table->dropColumn('is_visible');
            }
        });
    }
};
