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
        Schema::dropIfExists('user_menus');
        Schema::dropIfExists('menus');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Unused legacy tables
    }
};
