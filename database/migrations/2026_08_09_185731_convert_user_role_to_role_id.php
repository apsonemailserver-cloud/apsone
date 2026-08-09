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
        // 1. Add role_id foreign key column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });

        // 2. Populate role_id based on string role (ensure role exists in roles table)
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            if (!empty($user->role)) {
                $roleName = trim($user->role);
                
                // Get or create role in roles table
                $role = DB::table('roles')->where('name', $roleName)->first();
                if (!$role) {
                    $roleId = DB::table('roles')->insertGetId([
                        'name' => $roleName,
                        'label' => $roleName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $roleId = $role->id;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'role_id' => $roleId,
                ]);
            }
        }

        // 3. Drop old string role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add string role column
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable();
        });

        // 2. Restore string values from roles relation
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            if ($user->role_id) {
                $roleName = DB::table('roles')->where('id', $user->role_id)->value('name');
                DB::table('users')->where('id', $user->id)->update([
                    'role' => $roleName,
                ]);
            }
        }

        // 3. Drop role_id FK column
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
