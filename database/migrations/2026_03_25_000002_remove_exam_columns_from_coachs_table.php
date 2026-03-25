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
        Schema::table('coachs', function (Blueprint $table) {
            $table->dropColumn(['coach_exam_date', 'coach_exam_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coachs', function (Blueprint $table) {
            $table->date('coach_exam_date')->after('ts_id');
            $table->string('coach_exam_at')->after('coach_exam_date');
        });
    }
};
