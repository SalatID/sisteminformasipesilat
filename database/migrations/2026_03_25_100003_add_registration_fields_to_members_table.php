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
        Schema::table('members', function (Blueprint $table) {
            // Status: pending, approved, rejected
            $table->enum('registration_status', ['pending', 'approved', 'rejected'])->default('approved')->after('picture');
            
            // Track if member is self-registered or added by admin
            $table->boolean('is_self_registered')->default(false)->after('registration_status');
            
            // Admin who approved/rejected the registration
            $table->uuid('approved_by')->nullable()->after('is_self_registered');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            // Reason for rejection
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['registration_status', 'is_self_registered', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
