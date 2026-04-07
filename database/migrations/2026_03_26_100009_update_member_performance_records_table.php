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
        Schema::table('member_performance_records', function (Blueprint $table) {
            // Check if unit_id exists before dropping
            if (Schema::hasColumn('member_performance_records', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropIndex(['unit_id', 'training_date']);
                $table->dropColumn('unit_id');
            }

            // Add new columns only if they don't exist
            if (!Schema::hasColumn('member_performance_records', 'training_center_id')) {
                $table->uuid('training_center_id')->nullable()->after('member_id');
            }
            
            if (!Schema::hasColumn('member_performance_records', 'training_type')) {
                $table->enum('training_type', ['online', 'offline'])->default('offline')->after('training_center_id');
            }

            // Add new foreign key for training_center_id if it doesn't exist
            if (!Schema::hasColumn('member_performance_records', 'training_center_id')) {
                $table->foreign('training_center_id')->references('id')->on('training_centers')->onDelete('set null');
            }

            // Add new index with short name to avoid MySQL identifier length limit
            try {
                $table->index(['member_id', 'training_center_id', 'training_date'], 'idx_perf_member_tc_date');
            } catch (\Exception $e) {
                // Index might already exist, silently continue
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_performance_records', function (Blueprint $table) {
            // Drop the new foreign key
            $table->dropForeign(['training_center_id']);

            // Drop new columns
            $table->dropColumn(['training_center_id', 'training_type']);

            // Re-add the old unit_id column and foreign key
            $table->uuid('unit_id')->nullable()->after('training_date');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');

            // Re-add old index
            $table->index(['unit_id', 'training_date']);
        });
    }
};
