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
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('training_day');
            $table->uuid('pj_id');
            $table->time('training_hours_start');
            $table->time('training_hours_end');
            $table->string('paid_fee_type');
            $table->string('paid_periode');
            $table->string('school_pic_name');
            $table->string('school_pic_number');
            $table->string('school_level');
            $table->string('school_pic_occupation');
            $table->date('joined_date');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
