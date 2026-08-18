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
        if (Schema::hasTable('employees')) {
            if (!Schema::hasColumn('employees', 'station')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->string('station')->nullable()->after('fullname');
                });
            }

            // Backfill station data from users table if users table has station or station_id column
            if (Schema::hasTable('users')) {
                $hasUserStation = Schema::hasColumn('users', 'station');
                $hasUserStationId = Schema::hasColumn('users', 'station_id');

                if ($hasUserStation || $hasUserStationId) {
                    $users = DB::table('users')->whereNotNull('employee_id')->get();
                    foreach ($users as $user) {
                        $stationVal = null;
                        if ($hasUserStation && !empty($user->station)) {
                            $stationVal = $user->station;
                        } elseif ($hasUserStationId && !empty($user->station_id)) {
                            $stationVal = $user->station_id;
                        }

                        if ($stationVal) {
                            DB::table('employees')
                                ->where('id', $user->employee_id)
                                ->whereNull('station')
                                ->update(['station' => $stationVal]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'station')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('station');
            });
        }
    }
};
