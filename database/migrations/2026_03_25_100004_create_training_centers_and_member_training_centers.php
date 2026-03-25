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
        // Create training_centers table
        Schema::create('training_centers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create pivot table for member_training_centers
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
        Schema::dropIfExists('training_centers');
    }
};
