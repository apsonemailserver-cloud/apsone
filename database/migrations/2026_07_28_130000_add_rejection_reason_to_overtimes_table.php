<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('overtimes') && !Schema::hasColumn('overtimes', 'rejection_reason')) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('overtimes') && Schema::hasColumn('overtimes', 'rejection_reason')) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }
};
