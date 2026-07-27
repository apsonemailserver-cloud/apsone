<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('attendances', 'station_id')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('station_id')
                ->nullable()
                ->after('user_id')
                ->constrained('stations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('attendances', 'station_id')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_id');
        });
    }
};
