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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('job_title_id')->nullable()->after('gender')->constrained('job_titles')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('cluster')->constrained('units')->nullOnDelete();
            $table->foreignId('sub_unit_id')->nullable()->after('unit')->constrained('sub_units')->nullOnDelete();
        });

        // Migrate existing string data to IDs
        $jobTitles = DB::table('job_titles')->pluck('id', 'name');
        $units = DB::table('units')->pluck('id', 'name');
        $subUnits = DB::table('sub_units')->pluck('id', 'name');

        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $jtId = isset($user->job_title) && isset($jobTitles[$user->job_title]) ? $jobTitles[$user->job_title] : null;
            $uId = isset($user->unit) && isset($units[$user->unit]) ? $units[$user->unit] : null;
            $suId = isset($user->sub_unit) && isset($subUnits[$user->sub_unit]) ? $subUnits[$user->sub_unit] : null;

            if ($jtId || $uId || $suId) {
                DB::table('users')->where('id', $user->id)->update([
                    'job_title_id' => $jtId,
                    'unit_id' => $uId,
                    'sub_unit_id' => $suId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['job_title_id']);
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['sub_unit_id']);
            $table->dropColumn(['job_title_id', 'unit_id', 'sub_unit_id']);
        });
    }
};
