<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('schedule_details');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Table was legacy and un-used
    }
};
