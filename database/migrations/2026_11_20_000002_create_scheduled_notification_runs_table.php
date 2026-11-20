<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notification_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('scheduled_notification_id')
                ->constrained('scheduled_notifications')
                ->cascadeOnDelete();
            $table->dateTime('occurrence_at');
            $table->string('public_code', 32)->unique();
            $table->string('status', 16)->default('pending');
            $table->text('message_snapshot')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('next_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('provider_message_id', 128)->nullable()->index();
            $table->dateTime('sent_at')->nullable();

            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->string('cancel_source', 10)->nullable();
            $table->string('cancelled_by', 64)->nullable();
            $table->dateTime('announced_at')->nullable();
            $table->string('announcement_message_id', 128)->nullable();

            $table->timestamps();

            $table->unique(['scheduled_notification_id', 'occurrence_at'], 'sched_runs_schedule_occurrence_unique');
            $table->index(['status', 'occurrence_at']);
            $table->index(['status', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notification_runs');
    }
};
