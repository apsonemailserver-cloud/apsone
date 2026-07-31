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
        Schema::create('work_result_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_result_id');
            $table->string('user_id', 20); // NIP is string in users

            $table->foreign('work_result_id')->references('id')->on('work_results')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_result_user');
    }
};
