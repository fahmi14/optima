<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alokasi_transports', function (Blueprint $table) {
            $table->date('tgl_tugas')->nullable()->change();
            $table->unsignedSmallInteger('hari_ke')->default(1)->after('kecamatan_id');
            $table->string('jenis_perjalanan', 20)->default('dalam_kab')->after('hari_ke');
            $table->string('asal_perjalanan', 100)->nullable()->after('jenis_perjalanan');
            $table->string('tujuan_luar_kab', 150)->nullable()->after('asal_perjalanan');
            $table->json('rute_kecamatan_ids')->nullable()->after('tujuan_luar_kab');
            $table->boolean('tujuan_terisi')->default(true)->after('rute_kecamatan_ids');
        });

        Schema::table('penugasans', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_hari_transport')->default(0)->after('rute_transport');
        });
    }

    public function down(): void
    {
        Schema::table('penugasans', function (Blueprint $table) {
            $table->dropColumn('jumlah_hari_transport');
        });

        Schema::table('alokasi_transports', function (Blueprint $table) {
            $table->dropColumn([
                'hari_ke',
                'jenis_perjalanan',
                'asal_perjalanan',
                'tujuan_luar_kab',
                'rute_kecamatan_ids',
                'tujuan_terisi',
            ]);
        });
    }
};
