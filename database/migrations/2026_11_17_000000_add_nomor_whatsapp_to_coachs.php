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
        Schema::table('coachs', function (Blueprint $table) {
            $table->string('nomor_whatsapp', 20)->nullable()->after('ts_id')
                ->comment('Nomor WhatsApp pelatih (PJ unit), format E.164 tanpa +, contoh: 6281234567890');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coachs', function (Blueprint $table) {
            $table->dropColumn('nomor_whatsapp');
        });
    }
};
