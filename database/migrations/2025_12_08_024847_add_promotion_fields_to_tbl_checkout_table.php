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
        Schema::table('tbl_checkout', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_id')->nullable()->after('amount');
            $table->string('promotion_code')->nullable()->after('promotion_id');
            $table->integer('discount_amount')->default(0)->after('promotion_code');
            $table->integer('final_total')->default(0)->after('discount_amount');

            // Foreign key
            $table->foreign('promotion_id')->references('id')->on('tbl_promotions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tbl_checkout', function (Blueprint $table) {
            $table->dropForeign(['promotion_id']);
            $table->dropColumn(['promotion_id', 'promotion_code', 'discount_amount', 'final_total']);
        });
    }
};
