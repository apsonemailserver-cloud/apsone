<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_corrections') && !Schema::hasColumn('attendance_corrections', 'rejection_reason')) {
            Schema::table('attendance_corrections', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_corrections') && Schema::hasColumn('attendance_corrections', 'rejection_reason')) {
            Schema::table('attendance_corrections', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }
};
