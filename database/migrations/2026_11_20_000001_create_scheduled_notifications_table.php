<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 12)->unique();
            $table->string('name', 120);
            $table->string('chat_id', 64);
            $table->string('chat_label', 120)->nullable();
            $table->text('message');
            $table->text('holiday_message')->nullable();
            $table->string('frequency', 10);
            $table->time('time');
            $table->json('days_of_week')->nullable();
            $table->string('monthly_mode', 10)->nullable();
            $table->json('dates_of_month')->nullable();
            $table->json('weeks_of_month')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
