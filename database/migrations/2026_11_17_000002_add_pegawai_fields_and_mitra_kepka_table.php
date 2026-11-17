<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pangkat_golongan', 100)->nullable()->after('jabatan');
            $table->text('alamat')->nullable()->after('no_hp');
        });

        Schema::create('mitra_kepkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->year('tahun');
            $table->string('nama_lengkap');
            $table->string('posisi')->nullable();
            $table->string('status_seleksi')->nullable();
            $table->text('alamat_detail')->nullable();
            $table->string('alamat_provinsi')->nullable();
            $table->string('alamat_kab')->nullable();
            $table->string('alamat_kecamatan')->nullable();
            $table->string('alamat_desa')->nullable();
            $table->string('ttl')->nullable();
            $table->unsignedSmallInteger('umur')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('status_kawin', 50)->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('deskripsi_pekerjaan_lain')->nullable();
            $table->string('terikat_kldi', 20)->nullable();
            $table->string('nama_kldi')->nullable();
            $table->text('keterangan_kldi')->nullable();
            $table->string('no_telp', 50)->nullable();
            $table->string('sobat_id', 50)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'tahun'], 'mitra_kepka_user_tahun_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_kepkas');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pangkat_golongan', 'alamat']);
        });
    }
};
