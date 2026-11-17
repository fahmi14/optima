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
        // 1. Desas: id, id_kec, desa
        Schema::create('desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kec')->constrained('kecamatans')->cascadeOnDelete();
            $table->string('desa');
            $table->string('kd_desa', 20)->nullable();
            $table->timestamps();
        });

        // 2. SLS: id, id_kec, id_desa, id_sls, sls
        Schema::create('sls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kec')->constrained('kecamatans')->cascadeOnDelete();
            $table->foreignId('id_desa')->constrained('desas')->cascadeOnDelete();
            $table->string('id_sls', 50)->nullable()->index(); // kode/identitas SLS
            $table->string('sls'); // nama SLS (misal RT 001 RW 003)
            $table->timestamps();
        });

        // 3. Sub SLS: id, id_kec, id_desa, id_sls, subsls
        Schema::create('sub_sls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kec')->constrained('kecamatans')->cascadeOnDelete();
            $table->foreignId('id_desa')->constrained('desas')->cascadeOnDelete();
            $table->foreignId('sls_id')->nullable()->constrained('sls')->nullOnDelete();
            $table->string('id_sls', 50)->nullable()->index();
            $table->string('subsls'); // nama/kode Sub SLS
            $table->timestamps();
        });

        // 4. NKS: id, id_kec, id_desa, id_sls, sls, nks
        Schema::create('nks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kec')->constrained('kecamatans')->cascadeOnDelete();
            $table->foreignId('id_desa')->constrained('desas')->cascadeOnDelete();
            $table->string('id_sls', 50)->nullable();
            $table->string('sls')->nullable();
            $table->string('nks', 50)->index(); // kode NKS misal 3307010001
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nks');
        Schema::dropIfExists('sub_sls');
        Schema::dropIfExists('sls');
        Schema::dropIfExists('desas');
    }
};
