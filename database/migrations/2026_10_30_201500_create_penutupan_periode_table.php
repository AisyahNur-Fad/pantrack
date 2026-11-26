<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('penutupan_periode')) {
            Schema::create('penutupan_periode', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tim_id')->nullable()->constrained('tim')->cascadeOnDelete()->cascadeOnUpdate();
                $table->unsignedTinyInteger('periode_bulan');
                $table->unsignedSmallInteger('periode_tahun');
                $table->enum('status', ['terkunci', 'terbuka'])->default('terkunci');
                $table->foreignId('ditutup_oleh')->nullable()->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('waktu_tutup')->nullable();
                $table->foreignId('dibuka_oleh')->nullable()->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('waktu_buka')->nullable();
                $table->string('alasan_buka', 255)->nullable();
                $table->string('catatan', 255)->nullable();
                $table->timestamps();

                $table->index(['periode_tahun', 'periode_bulan', 'tim_id'], 'idx_penutupan_periode');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penutupan_periode');
    }
};

