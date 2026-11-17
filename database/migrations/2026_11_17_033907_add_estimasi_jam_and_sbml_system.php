<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah durasi estimasi jam per satuan pada sub_kegiatans
        Schema::table('sub_kegiatans', function (Blueprint $table) {
            $table->decimal('estimasi_jam_per_satuan', 8, 2)->default(1.00)->after('urutan');
            $table->decimal('honor_per_satuan', 15, 2)->default(0)->after('estimasi_jam_per_satuan');
        });

        // 2. Tambah estimasi jam & honor pada penugasans (menggantikan bobot poin murni)
        Schema::table('penugasans', function (Blueprint $table) {
            $table->decimal('estimasi_jam_total', 8, 2)->nullable()->after('bobot_poin');
            $table->decimal('honor_satuan', 15, 2)->default(0)->after('estimasi_jam_total');
            $table->decimal('honor_total', 15, 2)->default(0)->after('honor_satuan');
        });

        // 3. Tabel pengaturan sistem umum (key-value) untuk SBML dan jam kerja bulanan
        Schema::create('pengaturans', function (Blueprint $table) {
            $table->id();
            $table->string('kunci')->unique();
            $table->string('label');
            $table->text('nilai')->nullable();
            $table->string('tipe')->default('text'); // number, currency, text
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Insert nilai default SBML dan jam kerja per hari
        DB::table('pengaturans')->insert([
            [
                'kunci' => 'sbml_mitra_per_bulan',
                'label' => 'Standar Biaya Masukan Lainnya (SBML) Mitra Bulanan',
                'nilai' => '4000000',
                'tipe' => 'currency',
                'keterangan' => 'Batas maksimal akumulasi honor mitra per bulan (Rp)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kunci' => 'jam_kerja_per_hari',
                'label' => 'Standar Jam Kerja per Hari',
                'nilai' => '8',
                'tipe' => 'number',
                'keterangan' => 'Jam kerja standar per hari kerja untuk kalkulasi kuota bulanan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturans');

        Schema::table('penugasans', function (Blueprint $table) {
            $table->dropColumn(['estimasi_jam_total', 'honor_satuan', 'honor_total']);
        });

        Schema::table('sub_kegiatans', function (Blueprint $table) {
            $table->dropColumn(['estimasi_jam_per_satuan', 'honor_per_satuan']);
        });
    }
};
