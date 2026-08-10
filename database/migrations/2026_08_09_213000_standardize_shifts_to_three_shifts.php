<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No-op to prevent deleting or altering existing custom shifts/schedules.
        // The next migration (2026_08_10_081235_update_existing_shift_ids_to_p_s_m_format)
        // will handle renaming existing shift IDs to the new P/S/M format.
    }

    public function down(): void
    {
        // No-op
    }
};
