<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Satu jadwal dapat dikirim ke banyak chat (grup/pribadi).
 *
 * `scheduled_notifications.chat_id`/`chat_label` dipertahankan sebagai
 * target utama (kompatibilitas), sedangkan daftar lengkap target disimpan di
 * `scheduled_notification_targets` dengan urutan yang stabil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notification_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('scheduled_notification_id')
                ->constrained('scheduled_notifications')
                ->cascadeOnDelete();
            $table->string('chat_id', 64);
            $table->string('label', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['scheduled_notification_id', 'chat_id'], 'snt_schedule_chat_unique');
            $table->index(['scheduled_notification_id', 'sort_order'], 'snt_schedule_order_index');
        });

        // Migrasi data lama: satu jadwal = satu chat.
        DB::table('scheduled_notifications')
            ->whereNotNull('chat_id')
            ->where('chat_id', '!=', '')
            ->orderBy('created_at')
            ->select(['id', 'chat_id', 'chat_label', 'created_at', 'updated_at'])
            ->chunkById(200, function ($rows) {
                $now = now();

                foreach ($rows as $row) {
                    DB::table('scheduled_notification_targets')->insert([
                        'id' => (string) Str::uuid7(),
                        'scheduled_notification_id' => $row->id,
                        'chat_id' => $row->chat_id,
                        'label' => $row->chat_label,
                        'sort_order' => 0,
                        'created_at' => $row->created_at ?? $now,
                        'updated_at' => $row->updated_at ?? $now,
                    ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notification_targets');
    }
};
