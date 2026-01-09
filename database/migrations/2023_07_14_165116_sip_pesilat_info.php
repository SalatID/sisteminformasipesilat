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
        Schema::create('pesilats', function (Blueprint $table) {
            $table->id();
            $table->string('no_anggota',18);//SMIJKB-220101-0001
            $table->string('nama');
            $table->string('nama_panggilan')->nullable();
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->date('tanggal_bergabung')->nullable();
            $table->text('alamat');
            $table->integer('provinsi_id')->unnasign();
            $table->integer('kota_id')->unnasign();
            $table->integer('kode_pos')->unnasign();
            $table->string('nomor_telepon',13)->nullable();
            $table->string('kontak_darurat',13)->nullable();
            $table->string('email')->nullable();
            $table->string('golongan_darah',3)->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('minat_prestasi')->nullable();
            $table->string('kewarganergaraan')->nullable();
            $table->text('foto')->nullable();
            $table->integer('created_user');
            $table->integer('updated_user')->nullable()->unnasign();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['no_anggota']);
        });

        Schema::create('riwayat_units', function (Blueprint $table) {
            $table->id();
            $table->integer('pesilat_id')->unnasign();
            $table->integer('unit_sebelum_id')->unnasign();
            $table->integer('unit_sesudah_id')->unnasign()->nullable();
            $table->date('tanggal_pindah');
            $table->integer('created_user');
            $table->integer('updated_user')->nullable()->unnasign();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('riwayat_ukts', function (Blueprint $table) {
            $table->id();
            $table->integer('pesilat_id')->unnasign();
            $table->integer('sabuk_awal_id')->unnasign();
            $table->integer('sabuk_akhir_id')->unnasign()->nullable();
            $table->date('tanggal_ujian');
            $table->string('tempat_ujian');
            $table->string('penyelenggara_ujian');
            $table->string('nomor_sertifikat');
            $table->integer('created_user');
            $table->integer('updated_user')->nullable()->unnasign();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesilats');
        Schema::dropIfExists('riwayat_units');
        Schema::dropIfExists('riwayat_ukts');
    }
};
