<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Riwayat ekspor laporan Excel (tabel `ekspor_laporan`). */
class EksporLaporan extends Model
{
    protected $table = 'ekspor_laporan';

    protected $fillable = [
        'nama_berkas',
        'periode_bulan',
        'periode_tahun',
        'diekspor_oleh',
        'waktu_ekspor',
        'jumlah_baris',
        'lokasi_berkas',
    ];

    protected function casts(): array
    {
        return ['waktu_ekspor' => 'datetime'];
    }

    public function pengekspor(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diekspor_oleh');
    }
}
