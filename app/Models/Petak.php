<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Petak lahan (tabel `petak`). Kode petak unik di dalam satu distrik. */
class Petak extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_TIDAK_AKTIF = 'tidak_aktif';

    protected $table = 'petak';

    protected $fillable = [
        'distrik_id',
        'kode_petak',
        'nama_petak',
        'jenis_lahan',
        'luas_petak_ha',
        'status_petak',
        'keterangan',
    ];

    protected function casts(): array
    {
        return ['luas_petak_ha' => 'decimal:2'];
    }

    public static function statusPetak(): array
    {
        return [
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_TIDAK_AKTIF => 'Tidak aktif',
        ];
    }

    public function distrik(): BelongsTo
    {
        return $this->belongsTo(Distrik::class, 'distrik_id');
    }

    public function detailSpk(): HasMany
    {
        return $this->hasMany(DetailSpk::class, 'petak_id');
    }

    public function detailRealisasi(): HasMany
    {
        return $this->hasMany(DetailRealisasi::class, 'petak_id');
    }
}
