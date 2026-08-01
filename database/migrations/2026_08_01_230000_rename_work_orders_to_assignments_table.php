<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_orders') && !Schema::hasTable('assignments')) {
            Schema::rename('work_orders', 'assignments');
        }

        if (Schema::hasTable('work_order_user') && !Schema::hasTable('assignment_user')) {
            Schema::rename('work_order_user', 'assignment_user');

            Schema::table('assignment_user', function (Blueprint $table) {
                if (Schema::hasColumn('assignment_user', 'work_order_id')) {
                    $table->renameColumn('work_order_id', 'assignment_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignment_user') && !Schema::hasTable('work_order_user')) {
            Schema::table('assignment_user', function (Blueprint $table) {
                if (Schema::hasColumn('assignment_user', 'assignment_id')) {
                    $table->renameColumn('assignment_id', 'work_order_id');
                }
            });

            Schema::rename('assignment_user', 'work_order_user');
        }

        if (Schema::hasTable('assignments') && !Schema::hasTable('work_orders')) {
            Schema::rename('assignments', 'work_orders');
        }
    }
};
