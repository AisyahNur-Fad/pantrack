<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lampiran/dokumentasi laporan realisasi (tabel `lampiran_realisasi`). */
class LampiranRealisasi extends Model
{
    protected $table = 'lampiran_realisasi';

    protected $fillable = [
        'laporan_realisasi_id',
        'nama_berkas_asli',
        'lokasi_berkas',
        'jenis_lampiran',
        'diunggah_oleh',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanRealisasi::class, 'laporan_realisasi_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diunggah_oleh');
    }
}
