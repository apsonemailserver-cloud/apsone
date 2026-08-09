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
        // 1. Add new foreign key columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('unit')->constrained('units')->nullOnDelete();
            $table->foreignId('sub_unit_id')->nullable()->after('sub_unit')->constrained('sub_units')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->after('job_title')->constrained('job_titles')->nullOnDelete();
            $table->foreignId('cluster_id')->nullable()->after('cluster')->constrained('clusters')->nullOnDelete();
        });

        // 2. Populate new FK columns based on existing string names
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $unitId = null;
            $subUnitId = null;
            $jobTitleId = null;
            $clusterId = null;

            if (!empty($user->unit)) {
                $unitId = DB::table('units')->where('name', trim($user->unit))->value('id');
            }
            if (!empty($user->sub_unit)) {
                $subUnitId = DB::table('sub_units')->where('name', trim($user->sub_unit))->value('id');
            }
            if (!empty($user->job_title)) {
                $jobTitleId = DB::table('job_titles')->where('name', trim($user->job_title))->value('id');
            }
            if (!empty($user->cluster)) {
                $clusterId = DB::table('clusters')->where('name', trim($user->cluster))->value('id');
            }

            DB::table('users')->where('id', $user->id)->update([
                'unit_id' => $unitId,
                'sub_unit_id' => $subUnitId,
                'job_title_id' => $jobTitleId,
                'cluster_id' => $clusterId,
            ]);
        }

        // 3. Drop old string columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['unit', 'sub_unit', 'job_title', 'cluster']);
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
