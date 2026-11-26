<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarif pekerjaan beserta masa berlakunya (tabel `tarif_pekerjaan`).
 * Hanya menyimpan riwayat tarif; rumus HARGA/TOTAL belum ditetapkan perusahaan sehingga tidak dihitung di sini.
 */
class TarifPekerjaan extends Model
{
    protected $table = 'tarif_pekerjaan';

    protected $fillable = [
        'jenis_pekerjaan_id',
        'nilai_tarif',
        'berlaku_mulai',
        'berlaku_sampai',
        'keterangan',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'nilai_tarif' => 'decimal:2',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
        ];
    }

    public function jenisPekerjaan(): BelongsTo
    {
        return $this->belongsTo(JenisPekerjaan::class, 'jenis_pekerjaan_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh');
    }

    /**
     * Mengecek apakah tarif ini sedang aktif berlaku hari ini.
     */
    public function isAktif(): bool
    {
        $today = now()->toDateString();
        $mulai = $this->berlaku_mulai?->toDateString();
        $sampai = $this->berlaku_sampai?->toDateString();

        if (! $mulai) {
            return false;
        }

        return $mulai <= $today && ($sampai === null || $sampai >= $today);
    }

    /**
     * Mengecek apakah tarif ini pernah/sedang digunakan oleh transaksi SPK operasional.
     */
    public function digunakanDiSpk(): bool
    {
        $mulai = $this->berlaku_mulai?->toDateString();
        $sampai = $this->berlaku_sampai?->toDateString();

        if (! $mulai) {
            return false;
        }

        return Spk::where('jenis_pekerjaan_id', $this->jenis_pekerjaan_id)
            ->whereDate('tanggal_spk', '>=', $mulai)
            ->when($sampai, fn ($q) => $q->whereDate('tanggal_spk', '<=', $sampai))
            ->exists();
    }
}
