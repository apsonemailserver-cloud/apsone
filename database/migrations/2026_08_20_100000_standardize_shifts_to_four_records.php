<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('shifts')) {
            return;
        }

        // 1. Define standard 3 shifts + 1 OFF
        $standardShifts = [
            'P1' => [
                'id' => 'P1',
                'name' => 'Pagi',
                'description' => 'Jam Masuk',
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'tolerance_minutes' => 15,
                'use_manpower' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'S1' => [
                'id' => 'S1',
                'name' => 'Siang',
                'description' => 'Jam Masuk',
                'start_time' => '15:30:00',
                'end_time' => '23:30:00',
                'tolerance_minutes' => 15,
                'use_manpower' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'M1' => [
                'id' => 'M1',
                'name' => 'Malam',
                'description' => 'Jam Masuk',
                'start_time' => '01:00:00',
                'end_time' => '06:30:00',
                'tolerance_minutes' => 15,
                'use_manpower' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'off' => [
                'id' => 'off',
                'name' => 'Libur',
                'description' => 'Libur',
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'tolerance_minutes' => 0,
                'use_manpower' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Schema::disableForeignKeyConstraints();

        // Ensure the 4 standard shifts exist and have correct values
        foreach ($standardShifts as $shiftId => $shiftData) {
            $existing = DB::table('shifts')->where('id', $shiftId)->first();
            if ($existing) {
                DB::table('shifts')->where('id', $shiftId)->update([
                    'name' => $shiftData['name'],
                    'description' => $shiftData['description'],
                    'start_time' => $shiftData['start_time'],
                    'end_time' => $shiftData['end_time'],
                    'tolerance_minutes' => $shiftData['tolerance_minutes'],
                    'use_manpower' => $shiftData['use_manpower'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('shifts')->insert($shiftData);
            }
        }

        // Remap schedules to standard shifts
        if (Schema::hasTable('schedules')) {
            DB::table('schedules')->whereIn('shift_id', ['P', 'P2', 'P3', 'P4', 'P5', 'pagi'])->update(['shift_id' => 'P1']);
            DB::table('schedules')->whereIn('shift_id', ['S', 'S2', 'siang'])->update(['shift_id' => 'S1']);
            DB::table('schedules')->whereIn('shift_id', ['M', 'M2', 'malam'])->update(['shift_id' => 'M1']);
            DB::table('schedules')->whereNotIn('shift_id', ['P1', 'S1', 'M1', 'off'])->update(['shift_id' => 'off']);
        }

        // Delete any extra shifts that are not part of the standard 4
        DB::table('shifts')->whereNotIn('id', ['P1', 'S1', 'M1', 'off'])->delete();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse required
    }
};
