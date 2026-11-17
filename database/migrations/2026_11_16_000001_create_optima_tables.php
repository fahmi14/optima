<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tim_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->string('ketua_tim')->nullable();
            $table->timestamps();
        });

        Schema::create('kecamatans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tim_kerja_id')->nullable()->after('kapasitas_maks')->constrained('tim_kerjas')->nullOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->after('tim_kerja_id')->constrained('kecamatans')->nullOnDelete();
        });

        Schema::create('master_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tim_kerja_id')->constrained('tim_kerjas')->cascadeOnDelete();
            $table->string('nama');
            $table->year('tahun');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('periode_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_kegiatan_id')->constrained('master_kegiatans')->cascadeOnDelete();
            $table->string('nama_periode');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->decimal('pagu_transport', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sub_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_kegiatan_id')->constrained('periode_kegiatans')->cascadeOnDelete();
            $table->string('nama');
            $table->enum('jenis_tahapan', ['Teknis', 'Lapangan', 'Administrasi'])->default('Teknis');
            $table->date('deadline_tahapan')->nullable();
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });

        Schema::create('penugasans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_kegiatan_id')->constrained('sub_kegiatans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->nullOnDelete();
            $table->integer('target_volume')->default(1);
            $table->string('satuan_volume', 50)->default('Dokumen');
            $table->integer('realisasi_volume')->default(0);
            $table->unsignedTinyInteger('progres_persen')->default(0);
            $table->unsignedTinyInteger('bobot_poin')->default(1);
            $table->date('deadline_tugas')->nullable();
            $table->enum('status', ['Belum Mulai', 'Berjalan', 'Menunggu Verifikasi', 'Selesai', 'Revisi'])->default('Belum Mulai');
            $table->text('catatan')->nullable();
            $table->string('file_bukti')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('alokasi_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->constrained('penugasans')->cascadeOnDelete();
            $table->foreignId('periode_kegiatan_id')->constrained('periode_kegiatans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->nullOnDelete();
            $table->date('tgl_tugas');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->enum('status_spj', ['Draft', 'Layak', 'Dibayar', 'Ditolak'])->default('Draft');
            $table->text('catatan_spj')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tgl_tugas'], 'alokasi_user_tgl_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alokasi_transports');
        Schema::dropIfExists('penugasans');
        Schema::dropIfExists('sub_kegiatans');
        Schema::dropIfExists('periode_kegiatans');
        Schema::dropIfExists('master_kegiatans');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kecamatan_id');
            $table->dropConstrainedForeignId('tim_kerja_id');
        });

        Schema::dropIfExists('kecamatans');
        Schema::dropIfExists('tim_kerjas');
    }
};
