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
        Schema::table('exams', function (Blueprint $table) {
            // Add new columns
            $table->date('exam_end_date')->nullable()->after('exam_date');
            $table->uuid('ts_before')->nullable()->after('organizer');
            $table->uuid('ts_after')->nullable()->after('ts_before');

            // Add foreign keys
            $table->foreign('ts_before')->references('id')->on('ts')->onDelete('set null');
            $table->foreign('ts_after')->references('id')->on('ts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeignKey('exams_ts_before_foreign');
            $table->dropForeignKey('exams_ts_after_foreign');
            $table->dropColumn(['exam_end_date', 'ts_before', 'ts_after']);
        });
    }
};
