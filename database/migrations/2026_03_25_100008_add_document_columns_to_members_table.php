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
            // Required identification document numbers
            $table->string('citizen_number')->nullable()->after('picture');
            $table->string('family_card_number')->nullable()->after('citizen_number');
            $table->string('bpjs_number')->nullable()->after('family_card_number');
            
            // Optional identification document images
            $table->string('citizen_img')->nullable()->after('bpjs_number');
            $table->string('family_card_img')->nullable()->after('citizen_img');
            $table->string('bpjs_img')->nullable()->after('family_card_img');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'citizen_number',
                'family_card_number',
                'bpjs_number',
                'citizen_img',
                'family_card_img',
                'bpjs_img'
            ]);
        });
    }
};
