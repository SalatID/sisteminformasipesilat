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
        Schema::dropIfExists('pesilats');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate pesilats table if needed during rollback
        Schema::create('pesilats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
