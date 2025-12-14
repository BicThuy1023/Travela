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
        Schema::create('tbl_promotion_tour', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            // Dùng unsignedBigInteger để đảm bảo tương thích với tourId (có thể là bigint)
            $table->unsignedBigInteger('tour_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('promotion_id')->references('id')->on('tbl_promotions')->onDelete('cascade');
            // Bỏ foreign key cho tour_id vì có thể có vấn đề về kiểu dữ liệu
            // Sẽ dùng index thông thường thay vì foreign key
            $table->index('tour_id');
            
            // Unique constraint: một tour chỉ có thể được gán một lần cho một promotion
            $table->unique(['promotion_id', 'tour_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_promotion_tour');
    }
};
