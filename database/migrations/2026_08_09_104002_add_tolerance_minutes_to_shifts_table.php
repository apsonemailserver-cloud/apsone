<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts') && !Schema::hasColumn('shifts', 'tolerance_minutes')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->integer('tolerance_minutes')->default(15)->after('end_time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shifts') && Schema::hasColumn('shifts', 'tolerance_minutes')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->dropColumn('tolerance_minutes');
            });
        }
    }
};
