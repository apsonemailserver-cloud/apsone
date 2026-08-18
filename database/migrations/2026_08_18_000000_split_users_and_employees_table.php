<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create employees table
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('fullname')->nullable();
                $table->string('station')->nullable();
                $table->string('no_pas')->nullable();
                $table->string('phone')->nullable();
                $table->string('gender')->nullable();
                $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
                $table->string('tim_number')->nullable();
                $table->date('tim_registered')->nullable();
                $table->date('tim_expired')->nullable();
                $table->date('join_date')->nullable();
                $table->date('contract_start')->nullable();
                $table->date('contract_end')->nullable();
                $table->date('pas_registered')->nullable();
                $table->date('pas_expired')->nullable();
                $table->string('salary')->nullable();
                $table->boolean('is_qantas')->default(false);
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->foreignId('sub_unit_id')->nullable()->constrained('sub_units')->nullOnDelete();
                $table->date('tanggal_lahir')->nullable();
                $table->string('manager')->nullable();
                $table->string('senior_manager')->nullable();
                $table->string('status')->nullable();
                $table->text('alamat')->nullable();
                $table->string('pendidikan')->nullable();
                $table->string('domisili')->nullable();
                $table->string('kota_domisili')->nullable();
                $table->string('no_hp')->nullable();
                $table->string('bpjs_tk')->nullable();
                $table->string('bpjs_kesehatan')->nullable();
                $table->string('no_kk')->nullable();
                $table->string('no_nik')->nullable();
                $table->string('tempat_lahir')->nullable();
                $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 2. Add employee_id to users table if not exists
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
            }
        });

        // 3. Migrate data from users to employees
        $users = DB::table('users')->get();

        $employeeColumns = [
            'fullname', 'station', 'no_pas', 'phone', 'gender',
            'job_title_id', 'tim_number', 'tim_registered', 'tim_expired',
            'join_date', 'contract_start', 'contract_end', 'pas_registered',
            'pas_expired', 'salary', 'is_qantas', 'unit_id', 'sub_unit_id',
            'tanggal_lahir', 'manager', 'senior_manager', 'status', 'alamat',
            'pendidikan', 'domisili', 'kota_domisili', 'no_hp', 'bpjs_tk',
            'bpjs_kesehatan', 'no_kk', 'no_nik', 'tempat_lahir', 'cluster_id',
            'created_at', 'updated_at'
        ];

        foreach ($users as $user) {
            $employeeData = [];
            foreach ($employeeColumns as $col) {
                if (property_exists($user, $col)) {
                    $employeeData[$col] = $user->{$col};
                }
            }

            // Create employee record
            $employeeId = DB::table('employees')->insertGetId($employeeData);

            // Update user record with employee_id
            DB::table('users')->where('id', $user->id)->update([
                'employee_id' => $employeeId
            ]);
        }

        // 4. Add foreign key constraint for employee_id on users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // 5. Drop employee columns from users table
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) use ($employeeColumns) {
                foreach (['job_title_id', 'unit_id', 'sub_unit_id', 'cluster_id'] as $fkCol) {
                    if (Schema::hasColumn('users', $fkCol)) {
                        try {
                            $table->dropForeign([$fkCol]);
                        } catch (\Throwable $e) {}
                    }
                }
            });
        }

        Schema::table('users', function (Blueprint $table) use ($employeeColumns) {
            $colsToDrop = [];
            foreach ($employeeColumns as $col) {
                if (!in_array($col, ['created_at', 'updated_at']) && Schema::hasColumn('users', $col)) {
                    $colsToDrop[] = $col;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add employee columns to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('fullname')->nullable();
            $table->string('station')->nullable();
            $table->string('no_pas')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->string('tim_number')->nullable();
            $table->date('tim_registered')->nullable();
            $table->date('tim_expired')->nullable();
            $table->date('join_date')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->date('pas_registered')->nullable();
            $table->date('pas_expired')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->boolean('is_qantas')->default(false);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('sub_unit_id')->nullable()->constrained('sub_units')->nullOnDelete();
            $table->date('tanggal_lahir')->nullable();
            $table->string('manager')->nullable();
            $table->string('senior_manager')->nullable();
            $table->string('status')->nullable();
            $table->text('alamat')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('domisili')->nullable();
            $table->string('kota_domisili')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('bpjs_tk')->nullable();
            $table->string('bpjs_kesehatan')->nullable();
            $table->string('no_kk')->nullable();
            $table->string('no_nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
        });

        // 2. Restore data back to users table
        $users = DB::table('users')->whereNotNull('employee_id')->get();
        foreach ($users as $user) {
            $emp = DB::table('employees')->where('id', $user->employee_id)->first();
            if ($emp) {
                $empData = (array) $emp;
                unset($empData['id'], $empData['created_at'], $empData['updated_at']);
                DB::table('users')->where('id', $user->id)->update($empData);
            }
        }

        // 3. Drop employee_id foreign key and column
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });

        // 4. Drop employees table
        Schema::dropIfExists('employees');
    }
};
