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
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('ts_id');
            $table->date('joined_date');
            $table->string('member_id')->unique(); // Member ID/Registration Number
            $table->uuid('unit_id')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('school_level')->nullable();
            $table->string('picture')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();

            // Foreign keys
            $table->foreign('ts_id')->references('id')->on('ts')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
