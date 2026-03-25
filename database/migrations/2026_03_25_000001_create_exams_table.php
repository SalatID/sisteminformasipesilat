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
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('examinee_id');
            $table->string('examinee_type'); // 'coach' or 'member'
            $table->date('exam_date');
            $table->string('exam_location');
            $table->string('organizer')->nullable(); // replacing result
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();

            $table->index(['examinee_id', 'examinee_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
