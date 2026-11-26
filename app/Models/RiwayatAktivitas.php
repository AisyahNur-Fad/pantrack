<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Riwayat aktivitas (tabel `riwayat_aktivitas`) bersifat append-only:
 * catatan hanya dapat ditambah, tidak boleh diubah atau dihapus dari aplikasi.
 */
class RiwayatAktivitas extends Model
{
    /** Tabel hanya memiliki created_at. */
    public const UPDATED_AT = null;

    protected $table = 'riwayat_aktivitas';

    protected $fillable = [
        'pengguna_id',
        'nama_aktivitas',
        'jenis_data',
        'id_data',
        'keterangan',
        'alamat_ip',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Riwayat aktivitas tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Riwayat aktivitas tidak dapat dihapus.'));
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
