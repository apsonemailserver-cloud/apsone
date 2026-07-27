<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'job_title' => fn() => $table->string('job_title')->nullable()->after('role'),
                'cluster' => fn() => $table->string('cluster')->nullable(),
                'unit' => fn() => $table->string('unit')->nullable(),
                'sub_unit' => fn() => $table->string('sub_unit')->nullable(),
                'tanggal_lahir' => fn() => $table->date('tanggal_lahir')->nullable(),
                'manager' => fn() => $table->string('manager')->nullable(),
                'senior_manager' => fn() => $table->string('senior_manager')->nullable(),
                'status' => fn() => $table->string('status')->nullable(),
                'alamat' => fn() => $table->string('alamat')->nullable(),
                'pendidikan' => fn() => $table->string('pendidikan')->nullable(),
                'domisili' => fn() => $table->string('domisili')->nullable(),
                'kota_domisili' => fn() => $table->string('kota_domisili')->nullable(),
                'no_hp' => fn() => $table->string('no_hp')->nullable(),
                'bpjs_tk' => fn() => $table->string('bpjs_tk')->nullable(),
                'bpjs_kesehatan' => fn() => $table->string('bpjs_kesehatan')->nullable(),
            ];

            foreach ($columns as $name => $callback) {
                if (!Schema::hasColumn('users', $name)) {
                    $callback();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'job_title', 'cluster', 'unit', 'sub_unit',
                'tanggal_lahir', 'manager', 'senior_manager', 'status',
                'alamat', 'pendidikan', 'domisili', 'kota_domisili', 'no_hp',
                'bpjs_tk', 'bpjs_kesehatan'
            ]);
        });
    }
};
