<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = Schema::hasTable('assignments') ? 'assignments' : 'work_orders';
        Schema::table($tableName, function (Blueprint $table) {
            // Track which leader submitted this work order
            $table->string('submitted_by', 20)->nullable()->after('type');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('assignments') ? 'assignments' : 'work_orders';
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn('submitted_by');
        });
    }
};
