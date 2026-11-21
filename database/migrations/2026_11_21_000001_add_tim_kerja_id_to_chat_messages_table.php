<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('tim_kerja_id')->nullable()->after('receiver_id')->constrained('tim_kerjas')->cascadeOnDelete();
            $table->index(['tim_kerja_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['tim_kerja_id', 'created_at']);
            $table->dropConstrainedForeignId('tim_kerja_id');
        });
    }
};
