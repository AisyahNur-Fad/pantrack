<?php

use App\Models\AnggotaTim;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Hapus helper dari anggota_tim (Master Tim).
     * Helper merupakan warga lokal yang dinamis dan ditambahkan langsung oleh
     * pekerja lapangan saat membuat Laporan Realisasi sesuai kebutuhan per penerbangan.
     */
    public function up(): void
    {
        AnggotaTim::where('jabatan_tim', AnggotaTim::HELPER)->delete();
    }

    public function down(): void
    {
        //
    }
};

