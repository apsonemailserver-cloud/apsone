<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shifts')) {
            return;
        }

        // 1. Inisialisasi 4 Shift Standar (Pagi, Siang, Malam, Libur)
        $standardShifts = [
            [
                'id' => 'pagi',
                'name' => 'Pagi',
                'description' => 'Shift Pagi',
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'use_manpower' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'siang',
                'name' => 'Siang',
                'description' => 'Shift Siang',
                'start_time' => '15:00:00',
                'end_time' => '23:00:00',
                'use_manpower' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'malam',
                'name' => 'Malam',
                'description' => 'Shift Malam',
                'start_time' => '23:00:00',
                'end_time' => '07:00:00',
                'use_manpower' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'off',
                'name' => 'Libur',
                'description' => 'Libur / Off',
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'use_manpower' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($standardShifts as $shift) {
            DB::table('shifts')->updateOrInsert(['id' => $shift['id']], $shift);
        }

        // 2. Remap schedules yang menunjuk ke shift lama (A, B, C, D, E, F, G, H)
        if (Schema::hasTable('schedules')) {
            DB::table('schedules')->whereIn('shift_id', ['A', 'B', 'C', 'D'])->update(['shift_id' => 'pagi']);
            DB::table('schedules')->whereIn('shift_id', ['E', 'F'])->update(['shift_id' => 'siang']);
            DB::table('schedules')->whereIn('shift_id', ['G', 'H'])->update(['shift_id' => 'malam']);
            DB::table('schedules')->whereNotIn('shift_id', ['pagi', 'siang', 'malam', 'off'])->update(['shift_id' => 'off']);
        }

        // 3. Hapus data shift lama selain pagi, siang, malam, off
        DB::table('shifts')->whereNotIn('id', ['pagi', 'siang', 'malam', 'off'])->delete();
    }

    public function down(): void
    {
        // No-op
    }
};
