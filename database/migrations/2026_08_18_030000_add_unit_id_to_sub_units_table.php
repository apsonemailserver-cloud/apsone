<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sub_units')) {
            if (!Schema::hasColumn('sub_units', 'unit_id')) {
                Schema::table('sub_units', function (Blueprint $table) {
                    $table->foreignId('unit_id')->nullable()->after('name')->constrained('units')->nullOnDelete();
                });
            }

            // 1. Backfill sub_units.unit_id based on employee records
            if (Schema::hasTable('employees')) {
                $employeePairs = DB::table('employees')
                    ->whereNotNull('unit_id')
                    ->whereNotNull('sub_unit_id')
                    ->select('sub_unit_id', 'unit_id')
                    ->groupBy('sub_unit_id', 'unit_id')
                    ->get();

                foreach ($employeePairs as $pair) {
                    DB::table('sub_units')
                        ->where('id', $pair->sub_unit_id)
                        ->whereNull('unit_id')
                        ->update(['unit_id' => $pair->unit_id]);
                }
            }

            // 2. Backfill from users table if any unmapped sub_units remain
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'sub_unit_id') && Schema::hasColumn('users', 'unit_id')) {
                $userPairs = DB::table('users')
                    ->whereNotNull('unit_id')
                    ->whereNotNull('sub_unit_id')
                    ->select('sub_unit_id', 'unit_id')
                    ->groupBy('sub_unit_id', 'unit_id')
                    ->get();

                foreach ($userPairs as $pair) {
                    DB::table('sub_units')
                        ->where('id', $pair->sub_unit_id)
                        ->whereNull('unit_id')
                        ->update(['unit_id' => $pair->unit_id]);
                }
            }

            // 3. Fallback: For any remaining unmapped sub_units, match by name heuristics or assign default unit
            $firstUnit = DB::table('units')->first();
            if ($firstUnit) {
                DB::table('sub_units')
                    ->whereNull('unit_id')
                    ->update(['unit_id' => $firstUnit->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sub_units') && Schema::hasColumn('sub_units', 'unit_id')) {
            Schema::table('sub_units', function (Blueprint $table) {
                try {
                    $table->dropForeign(['unit_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('unit_id');
            });
        }
    }
};
