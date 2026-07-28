<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance_corrections')) {
            Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 20);
            $table->foreignId('attendance_id')
                ->nullable()
                ->constrained('attendances')
                ->nullOnDelete();
            $table->foreignId('station_id')
                ->constrained('stations');
            $table->date('attendance_date');
            $table->dateTime('proposed_check_in_time');
            $table->dateTime('proposed_check_out_time');
            $table->text('reason');
            $table->text('rejection_reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('decided_by', 20)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('decided_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
