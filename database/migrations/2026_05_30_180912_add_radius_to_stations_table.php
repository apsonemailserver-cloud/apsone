<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('stations', 'radius')) {
            return;
        }

        Schema::table('stations', function (Blueprint $table) {
            $table->integer('radius')->default(0)->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('stations', 'radius')) {
            return;
        }

        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('radius');
        });
    }
};
