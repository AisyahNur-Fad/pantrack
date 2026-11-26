<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Drone operasional (tabel `drone`). */
class Drone extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_PERAWATAN = 'perawatan';
    public const STATUS_TIDAK_BEROPERASI = 'tidak_beroperasi';

    protected $table = 'drone';

    protected $fillable = [
        'nomor_drone',
        'nama_drone',
        'tipe_drone',
        'status_operasional',
        'catatan_perawatan',
        'ambang_batas_servis_ha',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'ambang_batas_servis_ha' => 'decimal:2',
        ];
    }

    public function getLabelLengkapAttribute(): string
    {
        return $this->nomor_drone . ($this->nama_drone ? ' · ' . $this->nama_drone : '') . ' (' . $this->tipe_drone . ')';
    }

    public static function statusOperasional(): array
    {
        return [
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_PERAWATAN => 'Perawatan',
            self::STATUS_TIDAK_BEROPERASI => 'Tidak beroperasi',
        ];
    }

    public function tim(): HasMany
    {
        return $this->hasMany(Tim::class, 'drone_id');
    }

    /** Tim aktif yang saat ini memegang dan mengoperasikan drone ini. */
    public function timAktif(): HasOne
    {
        return $this->hasOne(Tim::class, 'drone_id')->where('status_tim', Tim::STATUS_AKTIF);
    }

    /** Lokasi penugasan saat ini (On Duty Distrik / Workshop / Standby Gudang). */
    public function getLokasiPenugasanAttribute(): string
    {
        if ($this->status_operasional === self::STATUS_PERAWATAN) {
            return 'Workshop / Perawatan';
        }

        if ($this->status_operasional === self::STATUS_TIDAK_BEROPERASI) {
            return 'Gudang Logistik (Nonaktif)';
        }

        if ($this->timAktif && $this->timAktif->distrik) {
            return 'On Duty: ' . $this->timAktif->distrik->nama_distrik;
        }

        return 'Standby (Gudang)';
    }

    /** Akumulasi total luas terbang (Ha) dari seluruh laporan realisasi yang telah disetujui. */
    public function getTotalLuasTerbangHaAttribute(): float
    {
        return (float) \Illuminate\Support\Facades\DB::table('detail_realisasi as d')
            ->join('laporan_realisasi as l', 'l.id', '=', 'd.laporan_realisasi_id')
            ->join('tim as t', 't.id', '=', 'l.tim_id')
            ->where('t.drone_id', $this->id)
            ->where('l.status_laporan', \App\Models\LaporanRealisasi::DISETUJUI)
            ->sum('d.luas_realisasi_ha');
    }

    /** Menentukan apakah unit drone sudah mencapai atau mendekati ambang batas servis berkala. */
    public function getPerluServisAttribute(): bool
    {
        return (float) $this->ambang_batas_servis_ha > 0 && $this->total_luas_terbang_ha >= (float) $this->ambang_batas_servis_ha;
    }

    /** Persentase ketercapaian jam terbang menuju batas servis. */
    public function getPersenBatasServisAttribute(): float
    {
        if ((float) $this->ambang_batas_servis_ha <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->total_luas_terbang_ha / (float) $this->ambang_batas_servis_ha) * 100, 1));
    }
}
