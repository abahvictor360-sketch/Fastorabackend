<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Points contact/consultation form notifications at hello@fastora.africa.
 *
 * MailSettings::notificationRecipient() already falls back to contact_email
 * (also hello@fastora.africa) whenever notification_email is blank, so this
 * only matters for a database where notification_email was set to something
 * else at some point — this makes the override match what the owner wants
 * explicitly, rather than relying on the blank-value fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->update(['notification_email' => 'hello@fastora.africa']);
    }

    public function down(): void
    {
        DB::table('site_settings')->update(['notification_email' => null]);
    }
};
