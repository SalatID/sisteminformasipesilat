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
            if (!Schema::hasColumn('member_performance_records', 'kas')) {
                $table->boolean('kas')->default(false)->after('attended')->comment('Kas/Contribution status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_performance_records', function (Blueprint $table) {
            if (Schema::hasColumn('member_performance_records', 'kas')) {
                $table->dropColumn('kas');
            }
        });
    }
};
