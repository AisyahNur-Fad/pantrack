<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Surat Perintah Kerja (tabel `spk`). Berisi target pekerjaan, bukan realisasi (BR-05). */
class Spk extends Model
{
    public const MENUNGGU_PERSETUJUAN = 'menunggu_persetujuan';
    public const PERLU_PERBAIKAN = 'perlu_perbaikan';
    public const DISETUJUI = 'disetujui';
    public const SELESAI = 'selesai';
    public const DITOLAK = 'ditolak';

    protected $table = 'spk';

    protected $fillable = [
        'nomor_spk',
        'tanggal_spk',
        'periode_bulan',
        'periode_tahun',
        'distrik_id',
        'tim_id',
        'jenis_pekerjaan_id',
        'diinput_oleh',
        'disetujui_oleh',
        'tanggal_persetujuan',
        'status_spk',
        'catatan_perbaikan',
        'berkas_spk',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_spk' => 'date',
            'tanggal_persetujuan' => 'datetime',
        ];
    }

    public static function statusSpk(): array
    {
        return [
            self::MENUNGGU_PERSETUJUAN => 'Menunggu persetujuan',
            self::PERLU_PERBAIKAN => 'Perlu perbaikan',
            self::DISETUJUI => 'Disetujui',
            self::SELESAI => 'Selesai',
            self::DITOLAK => 'Ditolak',
        ];
    }

    public function distrik(): BelongsTo
    {
        return $this->belongsTo(Distrik::class, 'distrik_id');
    }

    public function tim(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_id');
    }

    public function jenisPekerjaan(): BelongsTo
    {
        return $this->belongsTo(JenisPekerjaan::class, 'jenis_pekerjaan_id');
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diinput_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'disetujui_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailSpk::class, 'spk_id');
    }

    public function laporanRealisasi(): HasMany
    {
        return $this->hasMany(LaporanRealisasi::class, 'spk_id');
    }

    /**
     * Total target luas seluruh petak dalam SPK ini (Ha).
     */
    public function getTotalLuasTargetAttribute(): float
    {
        return (float) $this->detail->sum('luas_target_ha');
    }

    /**
     * Total target luas petak yang memerlukan layanan mapping drone (Ha).
     */
    public function getTotalLuasMappingAttribute(): float
    {
        return (float) $this->detail->where('perlu_mapping', true)->sum('luas_target_ha');
    }

    /**
     * Mengecek apakah ada minimal satu petak yang menggunakan layanan mapping.
     */
    public function getAdaLayananMappingAttribute(): bool
    {
        return $this->detail->contains('perlu_mapping', true);
    }

    /**
     * Estimasi total biaya pekerjaan pokok pada SPK ini (Rp).
     */
    public function getTotalBiayaPokokAttribute(): float
    {
        return (float) $this->detail->sum(fn ($d) => $d->subtotal_pokok);
    }

    /**
     * Estimasi total biaya layanan tambahan mapping pada SPK ini (Rp).
     */
    public function getTotalBiayaMappingAttribute(): float
    {
        return (float) $this->detail->sum(fn ($d) => $d->subtotal_mapping);
    }

    /**
     * Estimasi grand total anggaran biaya SPK (Pekerjaan Pokok + Layanan Mapping).
     */
    public function getTotalEstimasiBiayaAttribute(): float
    {
        return (float) $this->detail->sum(fn ($d) => $d->subtotal_biaya);
    }
}
