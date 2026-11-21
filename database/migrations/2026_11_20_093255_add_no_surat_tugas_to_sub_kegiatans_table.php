<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_kegiatans', function (Blueprint $table) {
            $table->string('no_surat_tugas', 100)->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('sub_kegiatans', function (Blueprint $table) {
            $table->dropColumn('no_surat_tugas');
        });
    }
};
