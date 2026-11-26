<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Detail pekerjaan per petak/tanggal pada laporan realisasi (tabel `detail_realisasi`). */
class DetailRealisasi extends Model
{
    protected $table = 'detail_realisasi';

    protected $fillable = [
        'laporan_realisasi_id',
        'tanggal_pekerjaan',
        'petak_id',
        'kode_petak_manual',
        'jenis_pekerjaan_id',
        'luas_realisasi_ha',
        'perlu_mapping',
        'status_pekerjaan',
        'keterangan_kendala',
        'tarif_per_ha',
        'tarif_mapping_per_ha',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pekerjaan' => 'date',
            'luas_realisasi_ha' => 'decimal:2',
            'perlu_mapping' => 'boolean',
            'tarif_per_ha' => 'decimal:2',
            'tarif_mapping_per_ha' => 'decimal:2',
        ];
    }

    public static function statusPekerjaan(): array
    {
        return [
            'selesai' => 'CLOSING (Selesai Tuntas)',
            'dalam_proses' => 'PROGRES (Pengerjaan Sebagian/Berjalan)',
            'belum_dimulai' => 'Belum dimulai',
            'menunggu_mapping' => 'Menunggu mapping',
            'proses_mapping' => 'Proses mapping',
            'menunggu_flight_mission' => 'Menunggu flight mission',
            'terkendala' => 'Terkendala',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanRealisasi::class, 'laporan_realisasi_id');
    }

    public function petak(): BelongsTo
    {
        return $this->belongsTo(Petak::class, 'petak_id');
    }

    public function jenisPekerjaan(): BelongsTo
    {
        return $this->belongsTo(JenisPekerjaan::class, 'jenis_pekerjaan_id');
    }

    public function getSubtotalPokokAttribute(): float
    {
        return round((float) $this->luas_realisasi_ha * (float) ($this->tarif_per_ha ?? 0), 2);
    }

    public function getSubtotalMappingAttribute(): float
    {
        if (! $this->perlu_mapping) {
            return 0.0;
        }

        return round((float) $this->luas_realisasi_ha * (float) ($this->tarif_mapping_per_ha ?? 0), 2);
    }

    public function getSubtotalBiayaAttribute(): float
    {
        return round($this->subtotal_pokok + $this->subtotal_mapping, 2);
    }

    public function getTotalTarifPerHaAttribute(): float
    {
        $pokok = (float) ($this->tarif_per_ha ?? 0);
        $mapping = $this->perlu_mapping ? (float) ($this->tarif_mapping_per_ha ?? 0) : 0.0;

        return round($pokok + $mapping, 2);
    }

    public function getNamaPekerjaanLengkapAttribute(): string
    {
        $nama = $this->jenisPekerjaan?->nama_pekerjaan ?? '-';
        if ($this->perlu_mapping) {
            $nama .= ' (+Mapping)';
        }

        return $nama;
    }
}
