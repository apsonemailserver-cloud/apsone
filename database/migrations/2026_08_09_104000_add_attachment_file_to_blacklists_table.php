<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blacklists') && !Schema::hasColumn('blacklists', 'attachment_file')) {
            Schema::table('blacklists', function (Blueprint $table) {
                $table->string('attachment_file')->nullable()->after('banned_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blacklists') && Schema::hasColumn('blacklists', 'attachment_file')) {
            Schema::table('blacklists', function (Blueprint $table) {
                $table->dropColumn('attachment_file');
            });
        }
    }
};
