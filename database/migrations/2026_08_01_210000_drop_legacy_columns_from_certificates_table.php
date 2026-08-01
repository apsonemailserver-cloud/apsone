<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $columnsToDrop = [
                'safety_management_system',
                'safety_management_system_registered',
                'safety_management_system_expired',
                'human_factors',
                'human_factors_registered',
                'human_factors_expired',
                'ramp_safety_airside_safety',
                'ramp_safety_airside_safety_registered',
                'ramp_safety_airside_safety_expired',
                'dangerous_goods_regulations',
                'dangerous_goods_regulations_registered',
                'dangerous_goods_regulations_expired',
                'aviation_security_awareness',
                'aviation_security_awareness_registered',
                'aviation_security_awareness_expired',
                'airport_emergency_plan',
                'airport_emergency_plan_registered',
                'airport_emergency_plan_expired',
                'ground_support_equipment_operation',
                'ground_support_equipment_operation_registered',
                'ground_support_equipment_operation_expired',
                'basic_first_aid',
                'basic_first_aid_registered',
                'basic_first_aid_expired',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('certificates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        // Legacy columns do not need to be restored
    }
};
