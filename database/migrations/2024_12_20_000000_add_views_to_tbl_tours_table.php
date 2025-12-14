<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('tbl_tours', 'views')) {
            Schema::table('tbl_tours', function (Blueprint $table) {
                $table->unsignedInteger('views')->default(0)->after('availability');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('tbl_tours', 'views')) {
            Schema::table('tbl_tours', function (Blueprint $table) {
                $table->dropColumn('views');
            });
        }
    }
};

