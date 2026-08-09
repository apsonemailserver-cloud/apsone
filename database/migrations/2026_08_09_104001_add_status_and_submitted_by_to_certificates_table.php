<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (!Schema::hasColumn('certificates', 'status')) {
                    $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Approved')->after('certificate_file');
                }
                if (!Schema::hasColumn('certificates', 'submitted_by')) {
                    $table->string('submitted_by')->nullable()->after('status');
                }
                if (!Schema::hasColumn('certificates', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('submitted_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (Schema::hasColumn('certificates', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('certificates', 'submitted_by')) {
                    $table->dropColumn('submitted_by');
                }
                if (Schema::hasColumn('certificates', 'rejection_reason')) {
                    $table->dropColumn('rejection_reason');
                }
            });
        }
    }
};
