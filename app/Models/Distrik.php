<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Distrik / wilayah kerja (tabel `distrik`). */
class Distrik extends Model
{
    protected $table = 'distrik';

    protected $fillable = ['pt_id', 'kode_distrik', 'nama_distrik', 'keterangan', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    /**
     * Singkatan kode distrik untuk prefix penamaan petak lapangan (contoh: DSR -> SR).
     */
    public function getSingkatanAttribute(): string
    {
        $kd = strtoupper(trim((string) $this->kode_distrik));
        if (str_starts_with($kd, 'D') && strlen($kd) === 3) {
            return substr($kd, 1);
        }
        return $kd ?: strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->nama_distrik), 0, 2));
    }

    /**
     * Relasi Belongs-To: Setiap Distrik dimiliki oleh satu PT.
     */
    public function pt(): BelongsTo
    {
        return $this->belongsTo(Pt::class, 'pt_id');
    }

    public function petak(): HasMany
    {
        return $this->hasMany(Petak::class, 'distrik_id');
    }

    public function tim(): HasMany
    {
        return $this->hasMany(Tim::class, 'distrik_id');
    }

    public function spk(): HasMany
    {
        return $this->hasMany(Spk::class, 'distrik_id');
    }
}
