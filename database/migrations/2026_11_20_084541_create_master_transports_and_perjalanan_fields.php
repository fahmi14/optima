<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_asal_id')->constrained('kecamatans')->cascadeOnDelete();
            $table->foreignId('kecamatan_tujuan_id')->constrained('kecamatans')->cascadeOnDelete();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['kecamatan_asal_id', 'kecamatan_tujuan_id'], 'master_transport_asal_tujuan_unique');
        });

        Schema::table('penugasans', function (Blueprint $table) {
            $table->string('jenis_perjalanan', 20)->default('dalam_kab')->after('kecamatan_id'); // dalam_kab | luar_kab
            $table->string('asal_perjalanan', 100)->nullable()->after('jenis_perjalanan');
            $table->string('tujuan_luar_kab', 150)->nullable()->after('asal_perjalanan');
            $table->json('rute_transport')->nullable()->after('tujuan_luar_kab');
        });
    }

    public function down(): void
    {
        Schema::table('penugasans', function (Blueprint $table) {
            $table->dropColumn(['jenis_perjalanan', 'asal_perjalanan', 'tujuan_luar_kab', 'rute_transport']);
        });

        Schema::dropIfExists('master_transports');
    }
};
