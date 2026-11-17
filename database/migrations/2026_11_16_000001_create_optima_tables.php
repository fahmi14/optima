<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kab')->nullable();
            $table->string('kd_kab')->nullable();
            $table->string('kec')->nullable();
            $table->string('kd_kec')->nullable();
            $table->string('desa')->nullable();
            $table->string('kd_desa')->nullable();
            $table->string('sls')->nullable();
            $table->string('kd_sls')->nullable();
            $table->string('subsls')->nullable();
            $table->timestamps();
        });

        Schema::create('tims', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('pj')->nullable();
            $table->timestamps();
        });

        Schema::create('organiks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('golongan')->nullable();
            $table->json('id_tim')->nullable(); // json array of tim IDs
            $table->timestamps();
        });

        Schema::create('mitras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama');
            $table->string('nik')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('no_hp')->nullable();
            $table->timestamps();
        });

        Schema::create('kegiatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_selesai')->nullable();
            $table->foreignId('id_tim')->nullable()->constrained('tims')->nullOnDelete();
            $table->json('administrasi')->nullable(); // honor, jenis_honor, sbml, jml_petugas, beban, etc.
            $table->timestamps();
        });

        Schema::create('matrik_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kegiatan')->constrained('kegiatans')->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->json('role')->nullable(); // JSON role e.g. ['PML', 'PCL']
            $table->foreignId('id_wilayah')->nullable()->constrained('wilayahs')->nullOnDelete();
            $table->date('tgl')->nullable();
            $table->integer('beban')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrik_tugas');
        Schema::dropIfExists('kegiatans');
        Schema::dropIfExists('mitras');
        Schema::dropIfExists('organiks');
        Schema::dropIfExists('tims');
        Schema::dropIfExists('wilayahs');
    }
};
