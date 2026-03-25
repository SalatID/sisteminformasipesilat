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
        Schema::create('member_performance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->date('training_date');
            $table->uuid('unit_id')->nullable();
            $table->integer('endurance')->nullable(); // Score 0-100
            $table->integer('strength')->nullable(); // Score 0-100
            $table->integer('technique')->nullable(); // Score 0-100
            $table->boolean('attended')->default(true); // Attendance flag
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();

            // Foreign keys
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');

            // Indexes for better query performance
            $table->index(['member_id', 'training_date']);
            $table->index(['unit_id', 'training_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_performance_records');
    }
};
