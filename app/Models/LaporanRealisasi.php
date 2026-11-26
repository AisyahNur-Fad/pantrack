<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Laporan realisasi pekerjaan (tabel `laporan_realisasi`). Satu laporan memiliki banyak detail petak. */
class LaporanRealisasi extends Model
{
    public const DRAF = 'draf';
    public const MENUNGGU_PEMERIKSAAN = 'menunggu_pemeriksaan';
    public const PERLU_PERBAIKAN = 'perlu_perbaikan';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK = 'ditolak';

    protected $table = 'laporan_realisasi';

    protected $fillable = [
        'nomor_laporan',
        'tim_id',
        'nama_tim_manual',
        'nama_pilot',
        'nama_copilot',
        'nama_helper',
        'spk_id',
        'tanggal_input',
        'periode_bulan',
        'periode_tahun',
        'distrik_id',
        'diinput_oleh',
        'diperiksa_oleh',
        'tanggal_pemeriksaan',
        'status_laporan',
        'ada_kendala',
        'catatan_kendala',
        'ada_bbm',
        'keterangan_tanpa_bbm',
        'catatan_pemeriksaan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_input' => 'date',
            'tanggal_pemeriksaan' => 'datetime',
            'ada_kendala' => 'boolean',
            'ada_bbm' => 'boolean',
        ];
    }

    public function getNomorLaporanAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        if ($this->spk?->nomor_spk) {
            $tgl = $this->tanggal_input?->translatedFormat('d M Y') 
                ?? $this->created_at?->translatedFormat('d M Y') 
                ?? now()->translatedFormat('d M Y');
            return "{$this->spk->nomor_spk} · {$tgl}";
        }

        if ($this->spk_id) {
            return 'SPK #' . $this->spk_id;
        }

        return 'Laporan #' . $this->id;
    }

    public static function statusLaporan(): array
    {
        return [
            self::DRAF => 'Draf',
            self::MENUNGGU_PEMERIKSAAN => 'Menunggu pemeriksaan',
            self::PERLU_PERBAIKAN => 'Perlu perbaikan',
            self::DISETUJUI => 'Disetujui',
            self::DITOLAK => 'Ditolak',
        ];
    }

    public function tim(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_id');
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'spk_id');
    }

    public function distrik(): BelongsTo
    {
        return $this->belongsTo(Distrik::class, 'distrik_id');
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diinput_oleh');
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diperiksa_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailRealisasi::class, 'laporan_realisasi_id');
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(LampiranRealisasi::class, 'laporan_realisasi_id');
    }

    public function transaksiBbm(): HasMany
    {
        return $this->hasMany(TransaksiBbm::class, 'laporan_realisasi_id');
    }

    /**
     * Mendapatkan daftar array nama helper lapangan (maksimal 3 orang).
     *
     * @return array<int, string>
     */
    public function getDaftarHelperAttribute(): array
    {
        if (empty($this->nama_helper)) {
            return [];
        }

        $items = array_map('trim', explode(',', $this->nama_helper));

        return array_values(array_filter($items, fn ($v) => $v !== ''));
    }

    public function getTotalLuasRealisasiAttribute(): float
    {
        return round((float) $this->detail->sum('luas_realisasi_ha'), 2);
    }

    public function getTotalLuasMappingAttribute(): float
    {
        return round((float) $this->detail->where('perlu_mapping', true)->sum('luas_realisasi_ha'), 2);
    }

    public function getAdaLayananMappingAttribute(): bool
    {
        return $this->detail->contains('perlu_mapping', true);
    }

    public function getTotalNilaiPokokAttribute(): float
    {
        return round((float) $this->detail->sum(fn ($d) => $d->subtotal_pokok), 2);
    }

    public function getTotalNilaiMappingAttribute(): float
    {
        return round((float) $this->detail->sum(fn ($d) => $d->subtotal_mapping), 2);
    }

    public function getTotalNilaiPekerjaanAttribute(): float
    {
        return round((float) $this->detail->sum(fn ($d) => $d->subtotal_biaya), 2);
    }
}
