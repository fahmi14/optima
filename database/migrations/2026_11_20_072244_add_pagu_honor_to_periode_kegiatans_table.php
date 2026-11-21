<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periode_kegiatans', function (Blueprint $table) {
            $table->decimal('pagu_honor', 15, 2)->default(0)->after('pagu_transport');
        });
    }

    public function down(): void
    {
        Schema::table('periode_kegiatans', function (Blueprint $table) {
            $table->dropColumn('pagu_honor');
        });
    }
};
