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
        Schema::table('attendance_details', function (Blueprint $table) {
            $table->uuid('attendance_id')->after('id');
            $table->foreign('attendance_id')->references('id')->on('attendances');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_details', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
            $table->dropColumn('attendance_id');
        });
    }
};