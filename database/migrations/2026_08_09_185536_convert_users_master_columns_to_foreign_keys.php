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
        // 1. Add new foreign key columns to users table if not already present
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'sub_unit_id')) {
                $table->foreignId('sub_unit_id')->nullable()->constrained('sub_units')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'job_title_id')) {
                $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'cluster_id')) {
                $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
            }
        });

        // 2. Populate new FK columns based on existing string names
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $unitId = null;
            $subUnitId = null;
            $jobTitleId = null;
            $clusterId = null;

            if (isset($user->unit) && !empty($user->unit)) {
                $unitId = DB::table('units')->where('name', trim($user->unit))->value('id');
            }
            if (isset($user->sub_unit) && !empty($user->sub_unit)) {
                $subUnitId = DB::table('sub_units')->where('name', trim($user->sub_unit))->value('id');
            }
            if (isset($user->job_title) && !empty($user->job_title)) {
                $jobTitleId = DB::table('job_titles')->where('name', trim($user->job_title))->value('id');
            }
            if (isset($user->cluster) && !empty($user->cluster)) {
                $clusterId = DB::table('clusters')->where('name', trim($user->cluster))->value('id');
            }

            $updateData = array_filter([
                'unit_id' => $unitId,
                'sub_unit_id' => $subUnitId,
                'job_title_id' => $jobTitleId,
                'cluster_id' => $clusterId,
            ], fn($v) => !is_null($v));

            if (!empty($updateData)) {
                DB::table('users')->where('id', $user->id)->update($updateData);
            }
        }

        // 3. Drop old string columns if present
        Schema::table('users', function (Blueprint $table) {
            $colsToDrop = array_filter(['unit', 'sub_unit', 'job_title', 'cluster'], fn($col) => Schema::hasColumn('users', $col));
            if (!empty($colsToDrop)) {
                $table->dropColumn(array_values($colsToDrop));
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add old string columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('unit')->nullable();
            $table->string('sub_unit')->nullable();
            $table->string('job_title')->nullable();
            $table->string('cluster')->nullable();
        });

        // 2. Restore string values from FK relations
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $unit = $user->unit_id ? DB::table('units')->where('id', $user->unit_id)->value('name') : null;
            $subUnit = $user->sub_unit_id ? DB::table('sub_units')->where('id', $user->sub_unit_id)->value('name') : null;
            $jobTitle = $user->job_title_id ? DB::table('job_titles')->where('id', $user->job_title_id)->value('name') : null;
            $cluster = $user->cluster_id ? DB::table('clusters')->where('id', $user->cluster_id)->value('name') : null;

            DB::table('users')->where('id', $user->id)->update([
                'unit' => $unit,
                'sub_unit' => $subUnit,
                'job_title' => $jobTitle,
                'cluster' => $cluster,
            ]);
        }

        // 3. Drop FK columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['sub_unit_id']);
            $table->dropForeign(['job_title_id']);
            $table->dropForeign(['cluster_id']);

            $table->dropColumn(['unit_id', 'sub_unit_id', 'job_title_id', 'cluster_id']);
        });
    }
};
