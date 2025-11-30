<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Nha Trang, Đà Nẵng,...
            $table->string('slug')->unique();       // nha-trang
            $table->string('region')->nullable();   // Miền Trung, Miền Bắc...
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->json('popular_places')->nullable(); // JSON lưu mảng địa điểm nổi bật
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_destinations');
    }
};
