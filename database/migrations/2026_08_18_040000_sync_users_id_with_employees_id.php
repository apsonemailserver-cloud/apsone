<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Ensure columns can store string IDs
            try {
                DB::statement('ALTER TABLE users DROP FOREIGN KEY users_employee_id_foreign;');
            } catch (\Throwable $e) {}

            DB::statement('ALTER TABLE employees MODIFY id VARCHAR(50) NOT NULL;');
            DB::statement('ALTER TABLE users MODIFY employee_id VARCHAR(50) NULL;');
            
            try {
                DB::statement('ALTER TABLE users ADD CONSTRAINT users_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE;');
            } catch (\Throwable $e) {}
        }

        // 2. Parse original user IDs from sql dump if present
        $sqlPath = base_path('apsonemy_laravel.sql');
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            preg_match_all('/\((\'?\w+\'?),\s*(\d+|NULL),\s*(\d+|NULL),\s*\'([^\']+)\'/i', $sql, $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                $origId = trim($m[1], "' ");
                $email = trim($m[4]);

                $u = DB::table('users')->where('email', $email)->first();
                if ($u) {
                    $currUserId = (string) $u->id;
                    $currEmpId = $u->employee_id ? (string) $u->employee_id : null;

                    if ($currEmpId && DB::table('employees')->where('id', $currEmpId)->exists()) {
                        DB::table('employees')->where('id', $currEmpId)->update(['id' => $origId]);
                        DB::table('users')->where('id', $currUserId)->update(['employee_id' => $origId]);
                    }

                    DB::table('users')->where('id', $currUserId)->update(['id' => $origId]);

                    // Update child tables
                    if (Schema::hasTable('announcement_reads')) {
                        DB::table('announcement_reads')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('announcements')) {
                        DB::table('announcements')->where('created_by', $currUserId)->update(['created_by' => $origId]);
                    }
                    if (Schema::hasTable('assignment_user')) {
                        DB::table('assignment_user')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('assignments')) {
                        DB::table('assignments')->where('submitted_by', $currUserId)->update(['submitted_by' => $origId]);
                    }
                    if (Schema::hasTable('attendance_corrections')) {
                        DB::table('attendance_corrections')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                        DB::table('attendance_corrections')->where('decided_by', $currUserId)->update(['decided_by' => $origId]);
                    }
                    if (Schema::hasTable('attendances')) {
                        DB::table('attendances')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('certificates')) {
                        DB::table('certificates')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('documents')) {
                        DB::table('documents')->where('created_by', $currUserId)->update(['created_by' => $origId]);
                        DB::table('documents')->where('updated_by', $currUserId)->update(['updated_by' => $origId]);
                    }
                    if (Schema::hasTable('leave_balances')) {
                        DB::table('leave_balances')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('leaves')) {
                        DB::table('leaves')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                        DB::table('leaves')->where('approved_by', $currUserId)->update(['approved_by' => $origId]);
                        DB::table('leaves')->where('rejected_by', $currUserId)->update(['rejected_by' => $origId]);
                    }
                    if (Schema::hasTable('overtimes')) {
                        DB::table('overtimes')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('sessions')) {
                        DB::table('sessions')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('user_menus')) {
                        DB::table('user_menus')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('cleaning_report_user')) {
                        DB::table('cleaning_report_user')->where('user_id', $currUserId)->update(['user_id' => $origId]);
                    }
                    if (Schema::hasTable('cleaning_reports')) {
                        DB::table('cleaning_reports')->where('created_by', $currUserId)->update(['created_by' => $origId]);
                    }
                    if (Schema::hasColumn('users', 'pic_id')) {
                        DB::table('users')->where('pic_id', $currUserId)->update(['pic_id' => $origId]);
                    }
                }
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
