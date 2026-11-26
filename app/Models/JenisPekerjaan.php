<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jenis pekerjaan drone, misalnya penyemprotan, penebaran, dan jasa mapping (tabel `jenis_pekerjaan`). */
class JenisPekerjaan extends Model
{
    public const TIPE_UTAMA = 'utama';
    public const TIPE_ADDON = 'addon';

    protected $table = 'jenis_pekerjaan';

    protected $fillable = [
        'kode_pekerjaan',
        'nama_pekerjaan',
        'tipe',
        'keterangan',
        'aktif',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public static function daftarTipe(): array
    {
        return [
            self::TIPE_UTAMA => 'Pekerjaan Pokok / Utama',
            self::TIPE_ADDON => 'Layanan / Jasa Tambahan (Add-on)',
        ];
    }

    public function getLabelTipeAttribute(): string
    {
        return self::daftarTipe()[$this->tipe] ?? ucfirst($this->tipe ?? self::TIPE_UTAMA);
    }

    public function getIsAddonAttribute(): bool
    {
        return $this->tipe === self::TIPE_ADDON;
    }

    /** Scope pekerjaan utama (muncul di dropdown SPK pokok). */
    public function scopeUtama(Builder $query): Builder
    {
        return $query->where('tipe', self::TIPE_UTAMA);
    }

    /** Scope layanan tambahan (add-on, seperti mapping). */
    public function scopeAddon(Builder $query): Builder
    {
        return $query->where('tipe', self::TIPE_ADDON);
    }

    public function tarif(): HasMany
    {
        return $this->hasMany(TarifPekerjaan::class, 'jenis_pekerjaan_id');
    }

    public function spk(): HasMany
    {
        return $this->hasMany(Spk::class, 'jenis_pekerjaan_id');
    }

    /**
     * Mendapatkan tarif yang sedang aktif berlaku hari ini.
     */
    public function getTarifAktifAttribute(): ?TarifPekerjaan
    {
        $today = now()->toDateString();

        return $this->tarif()
            ->where('berlaku_mulai', '<=', $today)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $today))
            ->latest('berlaku_mulai')
            ->first();
    }
}
