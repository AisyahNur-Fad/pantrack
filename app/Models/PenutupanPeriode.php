<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Penutupan / Closing Periode Pembukuan Keuangan Unit Drone.
 *
 * Mengatur siklus tutup buku bulanan:
 *  - Periode berstatus 'terkunci': kasbon lama tetap terhitung dalam pembukuan,
 *    namun penambahan kasbon dan pengeluaran baru dilarang.
 *  - Dapat dibuka kembali ('terbuka') oleh Admin/Atasan jika memerlukan revisi.
 */
class PenutupanPeriode extends Model
{
    protected $table = 'penutupan_periode';

    public const STATUS_TERKUNCI = 'terkunci';
    public const STATUS_TERBUKA = 'terbuka';

    protected $fillable = [
        'tim_id',
        'periode_bulan',
        'periode_tahun',
        'status',
        'ditutup_oleh',
        'waktu_tutup',
        'dibuka_oleh',
        'waktu_buka',
        'alasan_buka',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_tutup' => 'datetime',
            'waktu_buka' => 'datetime',
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
        ];
    }

    public function tim(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_id');
    }

    public function penutup(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'ditutup_oleh');
    }

    public function pembuka(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuka_oleh');
    }

    /**
     * Memeriksa apakah suatu periode tertentu berstatus terkunci.
     */
    public static function apakahTerkunci(int $bulan, int $tahun, ?int $timId = null): bool
    {
        return static::query()
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('status', self::STATUS_TERKUNCI)
            ->when($timId, function ($q) use ($timId) {
                $q->where(function ($sub) use ($timId) {
                    $sub->where('tim_id', $timId)->orWhereNull('tim_id');
                });
            })
            ->exists();
    }
}

