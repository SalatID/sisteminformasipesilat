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
        Schema::create('contribution_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contribution_id');
            $table->uuid('coach_id');
            $table->decimal('multiplier', 5, 2);
            $table->integer('attendance');
            $table->tinyInteger('is_pj')->default(0);
            $table->decimal('final_value', 10, 2);
            $table->decimal('amount_per_attendance', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();

            // Foreign key constraints
            $table->foreign('contribution_id')->references('id')->on('contributions')->onDelete('cascade');
            $table->foreign('coach_id')->references('id')->on('coachs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_details');
    }
};
