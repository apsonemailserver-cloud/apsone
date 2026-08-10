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

        $mapping = [
            '9' => 'P1',
            'A' => 'P2',
            'B' => 'P3',
            'C' => 'P4',
            'D' => 'P5',
            'E' => 'S1',
            'F' => 'S2',
            'G' => 'M1',
            'H' => 'M2',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($mapping as $oldId => $newId) {
            $oldIdStr = (string)$oldId;
            DB::table('shifts')->where('id', $oldIdStr)->update(['id' => $newId]);
            if (Schema::hasTable('schedules')) {
                DB::table('schedules')->where('shift_id', $oldIdStr)->update(['shift_id' => $newId]);
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('shifts')) {
            return;
        }

        $reverseMapping = [
            'P1' => '9',
            'P2' => 'A',
            'P3' => 'B',
            'P4' => 'C',
            'P5' => 'D',
            'S1' => 'E',
            'S2' => 'F',
            'M1' => 'G',
            'M2' => 'H',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($reverseMapping as $newId => $oldId) {
            $newIdStr = (string)$newId;
            $oldIdStr = (string)$oldId;
            DB::table('shifts')->where('id', $newIdStr)->update(['id' => $oldIdStr]);
            if (Schema::hasTable('schedules')) {
                DB::table('schedules')->where('shift_id', $newIdStr)->update(['shift_id' => $oldIdStr]);
            }
        }

        Schema::enableForeignKeyConstraints();
    }
};
