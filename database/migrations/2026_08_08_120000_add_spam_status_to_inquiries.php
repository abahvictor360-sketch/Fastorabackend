<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds 'spam' to the inquiries.status enum, so ContactController can flag an
 * obvious agency pitch without the insert failing — the column was created
 * with a fixed ['new', 'contacted', 'closed'] list that never anticipated a
 * fourth value.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE inquiries MODIFY status ENUM('new', 'contacted', 'closed', 'spam') NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        DB::table('inquiries')->where('status', 'spam')->update(['status' => 'new']);

        DB::statement("ALTER TABLE inquiries MODIFY status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new'");
    }
};
