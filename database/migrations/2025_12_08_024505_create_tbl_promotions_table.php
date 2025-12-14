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
        Schema::create('tbl_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên chương trình
            $table->string('code')->unique(); // Mã giảm giá (unique)
            $table->text('description')->nullable(); // Mô tả
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent'); // Loại giảm: % hoặc số tiền cố định
            $table->integer('discount_value'); // Giá trị giảm
            $table->integer('min_order_amount')->default(0); // Giá trị đơn tối thiểu
            $table->integer('max_discount_amount')->default(0); // Giảm tối đa nếu là % (0 = không giới hạn)
            $table->enum('apply_type', ['global', 'specific_tours'])->default('global'); // Áp dụng toàn bộ hoặc theo tour
            $table->date('start_date'); // Ngày bắt đầu
            $table->date('end_date'); // Ngày kết thúc
            $table->integer('usage_limit')->default(0); // Tổng lượt dùng toàn hệ thống (0 = không giới hạn)
            $table->integer('per_user_limit')->default(0); // Số lần / 1 user (0 = không giới hạn)
            $table->integer('usage_count')->default(0); // Đếm số lượt đã dùng
            $table->boolean('is_active')->default(1); // Trạng thái hoạt động
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
        Schema::dropIfExists('tbl_promotions');
    }
};
