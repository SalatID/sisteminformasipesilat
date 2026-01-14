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
        Schema::create('contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('unit_id');
            $table->string('periode', 7); // Format: YYYY-MM
            $table->decimal('contribution_amount', 15, 2);
            $table->decimal('pj_share', 15, 2);
            $table->decimal('pj_percentage', 5, 2)->default(65.00);
            $table->decimal('kas_share', 15, 2);
            $table->decimal('kas_percentage', 5, 2)->default(20.00);
            $table->decimal('saving_share', 15, 2);
            $table->decimal('saving_percentage', 5, 2)->default(15.00);
            $table->decimal('difference', 15, 2)->default(0.00);
            $table->integer('revision_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();

            // Foreign key constraints
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            
            // Unique constraint for unit and periode
            $table->unique(['unit_id', 'periode', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
