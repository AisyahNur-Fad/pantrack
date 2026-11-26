<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lampiran bukti transaksi BBM (tabel `lampiran_bbm`). */
class LampiranBbm extends Model
{
    protected $table = 'lampiran_bbm';

    protected $fillable = ['transaksi_bbm_id', 'nama_berkas_asli', 'lokasi_berkas', 'diunggah_oleh'];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiBbm::class, 'transaksi_bbm_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diunggah_oleh');
    }
}
