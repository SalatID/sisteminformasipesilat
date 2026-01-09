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
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('unit_id');
            $table->enum('attendance_status', ['training', 'cancelled', 'school_holiday', 'ramadhan_break']);
            $table->date('attendance_date');
            $table->integer('new_member_cnt');
            $table->integer('old_member_cnt');
            $table->uuid('report_maker_id');
            $table->string('attendance_image')->nullable();
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
        Schema::dropIfExists('attendances');
    }
};
