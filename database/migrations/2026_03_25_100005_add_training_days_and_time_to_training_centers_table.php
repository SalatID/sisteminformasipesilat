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
        Schema::table('training_centers', function (Blueprint $table) {
            // training_days: JSON array of days (Monday, Tuesday, etc. or 1-7 representation)
            // Updated format: ["Monday", "Wednesday"] or ["Senin", "Rabu"]
            $table->json('training_days')->nullable()->after('email');
            
            // training_time: time field or string like "10:00-12:00"
            $table->string('training_time')->nullable()->after('training_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_centers', function (Blueprint $table) {
            $table->dropColumn(['training_days', 'training_time']);
        });
    }
};
