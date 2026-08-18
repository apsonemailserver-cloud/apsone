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
            if (!Schema::hasColumn('users', 'job_title_id')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->unsignedBigInteger('job_title_id')->nullable();
                } else {
                    $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
                }
            }
            if (!Schema::hasColumn('users', 'unit_id')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->unsignedBigInteger('unit_id')->nullable();
                } else {
                    $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                }
            }
            if (!Schema::hasColumn('users', 'sub_unit_id')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->unsignedBigInteger('sub_unit_id')->nullable();
                } else {
                    $table->foreignId('sub_unit_id')->nullable()->constrained('sub_units')->nullOnDelete();
                }
            }
        });

        // Migrate existing string data to IDs, dynamically creating master entries if missing
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $jtId = null;
            if (isset($user->job_title) && !empty($user->job_title)) {
                $trimmed = trim($user->job_title);
                $jtId = DB::table('job_titles')->where('name', $trimmed)->value('id');
                if (!$jtId) {
                    $jtId = DB::table('job_titles')->insertGetId([
                        'name' => $trimmed,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $uId = null;
            if (isset($user->unit) && !empty($user->unit)) {
                $trimmed = trim($user->unit);
                $uId = DB::table('units')->where('name', $trimmed)->value('id');
                if (!$uId) {
                    $uId = DB::table('units')->insertGetId([
                        'name' => $trimmed,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $suId = null;
            if (isset($user->sub_unit) && !empty($user->sub_unit)) {
                $trimmed = trim($user->sub_unit);
                $suId = DB::table('sub_units')->where('name', $trimmed)->value('id');
                if (!$suId) {
                    $suId = DB::table('sub_units')->insertGetId([
                        'name' => $trimmed,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

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
