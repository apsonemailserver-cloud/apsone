<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('wo_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Revert: set existing NULLs to empty string first, then make NOT NULL
            DB::statement("UPDATE assignments SET wo_number = '' WHERE wo_number IS NULL");
            $table->string('wo_number')->nullable(false)->change();
        });
    }
};
