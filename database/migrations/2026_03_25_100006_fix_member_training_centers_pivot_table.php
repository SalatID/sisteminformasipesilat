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
        // Drop the problematic pivot table
        Schema::dropIfExists('member_training_centers');

        // Recreate with proper composite key structure
        Schema::create('member_training_centers', function (Blueprint $table) {
            $table->uuid('member_id');
            $table->uuid('training_center_id');
            $table->date('joined_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->foreign('training_center_id')->references('id')->on('training_centers')->cascadeOnDelete();

            // Composite primary key
            $table->primary(['member_id', 'training_center_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_training_centers');

        // Recreate old structure if needed
        Schema::create('member_training_centers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('training_center_id');
            $table->date('joined_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->foreign('training_center_id')->references('id')->on('training_centers')->cascadeOnDelete();
            $table->unique(['member_id', 'training_center_id']);
        });
    }
};
