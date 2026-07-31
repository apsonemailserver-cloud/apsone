<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_results') && !Schema::hasTable('work_orders')) {
            Schema::table('work_result_user', function (Blueprint $table) {
                $table->dropForeign(['work_result_id']);
            });

            Schema::rename('work_results', 'work_orders');
            Schema::rename('work_result_user', 'work_order_user');

            Schema::table('work_order_user', function (Blueprint $table) {
                $table->renameColumn('work_result_id', 'work_order_id');
                $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('work_orders') && !Schema::hasTable('work_results')) {
            Schema::table('work_order_user', function (Blueprint $table) {
                $table->dropForeign(['work_order_id']);
            });

            Schema::rename('work_orders', 'work_results');
            Schema::rename('work_order_user', 'work_result_user');

            Schema::table('work_result_user', function (Blueprint $table) {
                $table->renameColumn('work_order_id', 'work_result_id');
                $table->foreign('work_result_id')->references('id')->on('work_results')->onDelete('cascade');
            });
        }
    }
};
