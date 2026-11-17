<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->json('notif_sent_channels')->nullable()->after('is_notif_send')
                ->comment('Provider notifikasi yang sudah sukses mengirim laporan ini, contoh: ["telegram","whatsapp"]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('notif_sent_channels');
        });
    }
};
