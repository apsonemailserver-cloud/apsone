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
        Schema::dropIfExists('freelances');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('freelances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->default('Freelance');
            $table->date('join_date')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
};
