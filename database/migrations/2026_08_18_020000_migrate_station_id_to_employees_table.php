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
        // 1. Add station_id column to employees table if not exists
        if (Schema::hasTable('employees')) {
            if (!Schema::hasColumn('employees', 'station_id')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->string('station_id', 15)->nullable()->after('fullname');
                });
            }

            // 2. Backfill station_id data in employees
            $stations = DB::table('stations')->get();
            $stationCodes = $stations->pluck('code')->toArray();

            $employees = DB::table('employees')->get();
            foreach ($employees as $emp) {
                $stationVal = null;
                if (property_exists($emp, 'station') && !empty($emp->station)) {
                    $stationVal = $emp->station;
                }

                // If not found on employee, check associated user if users.station_id or users.station exists
                if (empty($stationVal) && Schema::hasTable('users')) {
                    $user = DB::table('users')->where('employee_id', $emp->id)->first();
                    if ($user) {
                        if (property_exists($user, 'station_id') && !empty($user->station_id)) {
                            $stationVal = $user->station_id;
                        } elseif (property_exists($user, 'station') && !empty($user->station)) {
                            $stationVal = $user->station;
                        }
                    }
                }

                if (!empty($stationVal)) {
                    // Try exact match with station code
                    if (in_array($stationVal, $stationCodes)) {
                        DB::table('employees')->where('id', $emp->id)->update(['station_id' => $stationVal]);
                    } else {
                        // Match by station name
                        $matchedStation = $stations->first(function ($st) use ($stationVal) {
                            return strtolower($st->name) === strtolower($stationVal) || strtolower($st->code) === strtolower($stationVal);
                        });
                        if ($matchedStation) {
                            DB::table('employees')->where('id', $emp->id)->update(['station_id' => $matchedStation->code]);
                        } else {
                            // Fallback update as is
                            DB::table('employees')->where('id', $emp->id)->update(['station_id' => $stationVal]);
                        }
                    }
                }
            }

            // 3. Add foreign key on employees.station_id
            Schema::table('employees', function (Blueprint $table) {
                try {
                    $table->foreign('station_id')->references('code')->on('stations')->nullOnDelete();
                } catch (\Throwable $e) {}
            });

            // 4. Drop legacy station column from employees table if exists
            if (Schema::hasColumn('employees', 'station')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropColumn('station');
                });
            }
        }

        // 5. Drop station_id and station columns from users table if exist
        if (Schema::hasTable('users')) {
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table('users', function (Blueprint $table) {
                    foreach (['station_id', 'station'] as $col) {
                        if (Schema::hasColumn('users', $col)) {
                            try {
                                $table->dropForeign([$col]);
                            } catch (\Throwable $e) {}
                        }
                    }
                });
            }

            Schema::table('users', function (Blueprint $table) {
                $colsToDrop = [];
                if (Schema::hasColumn('users', 'station_id')) {
                    $colsToDrop[] = 'station_id';
                }
                if (Schema::hasColumn('users', 'station')) {
                    $colsToDrop[] = 'station';
                }
                if (!empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'station_id')) {
                    $table->string('station_id', 15)->nullable();
                }
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'station')) {
                    $table->string('station')->nullable();
                }
                if (Schema::hasColumn('employees', 'station_id')) {
                    try {
                        $table->dropForeign(['station_id']);
                    } catch (\Throwable $e) {}
                    $table->dropColumn('station_id');
                }
            });
        }
    }
};
