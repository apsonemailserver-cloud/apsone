<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (Schema::hasColumn('leave_types', 'min_notice_days')) {
                $table->dropColumn('min_notice_days');
            }
            if (Schema::hasColumn('leave_types', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
            if (Schema::hasColumn('leave_types', 'requires_attachment')) {
                $table->dropColumn('requires_attachment');
            }
            if (Schema::hasColumn('leave_types', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('leave_types', 'category')) {
                $table->dropColumn('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_types', 'min_notice_days')) {
                $table->integer('min_notice_days')->default(0);
            }
            if (!Schema::hasColumn('leave_types', 'is_paid')) {
                $table->boolean('is_paid')->default(true);
            }
            if (!Schema::hasColumn('leave_types', 'requires_attachment')) {
                $table->boolean('requires_attachment')->default(false);
            }
            if (!Schema::hasColumn('leave_types', 'code')) {
                $table->string('code')->unique()->nullable();
            }
        });
    }
};
