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
        Schema::table('sub_kegiatans', function (Blueprint $table) {
            $table->string('level_wilayah', 30)->nullable()->default('kecamatan')->after('jenis_tahapan');
            // level_wilayah: 'kecamatan', 'desa', 'sls', 'sub_sls', 'nks'
            $table->boolean('ada_pengawas')->default(false)->after('level_wilayah');
        });

        Schema::table('penugasans', function (Blueprint $table) {
            $table->string('level_wilayah', 30)->nullable()->default('kecamatan')->after('kecamatan_id');
            $table->json('wilayah_tugas')->nullable()->after('level_wilayah');
            $table->foreignId('pengawas_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penugasans', function (Blueprint $table) {
            $table->dropForeign(['pengawas_id']);
            $table->dropColumn(['level_wilayah', 'wilayah_tugas', 'pengawas_id']);
        });

        Schema::table('sub_kegiatans', function (Blueprint $table) {
            $table->dropColumn(['level_wilayah', 'ada_pengawas']);
        });
    }
};
