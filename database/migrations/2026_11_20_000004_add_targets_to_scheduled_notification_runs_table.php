<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu run dikirim ke banyak chat target. Kolom ini mencatat target mana yang
 * sudah menerima pesan sehingga retry/kirim ulang tidak mengirim dobel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_notification_runs', function (Blueprint $table) {
            $table->json('sent_targets')->nullable()->after('provider_message_id');
            $table->json('error_targets')->nullable()->after('sent_targets');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_notification_runs', function (Blueprint $table) {
            $table->dropColumn(['sent_targets', 'error_targets']);
        });
    }
};
