<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Transaksi BBM per tim (tabel `transaksi_bbm`). Hanya transaksi terverifikasi yang memengaruhi saldo resmi. */
class TransaksiBbm extends Model
{
    public const STOK_AWAL = 'stok_awal';
    public const PENGAJUAN = 'pengajuan';
    public const PENERIMAAN = 'penerimaan';
    public const PENGGUNAAN = 'penggunaan';
    public const PENYESUAIAN = 'penyesuaian';

    public const MENUNGGU_PEMERIKSAAN = 'menunggu_pemeriksaan';
    public const TERVERIFIKASI = 'terverifikasi';
    public const PERLU_PERBAIKAN = 'perlu_perbaikan';
    public const DITOLAK = 'ditolak';

    protected $table = 'transaksi_bbm';

    protected $fillable = [
        'nomor_transaksi',
        'tim_id',
        'jenis_bbm_id',
        'jenis_transaksi',
        'tanggal_transaksi',
        'jumlah_liter',
        'harga_per_liter',
        'total_biaya',
        'nomor_struk_nota',
        'keperluan',
        'keterangan',
        'status_verifikasi',
        'laporan_realisasi_id',
        'diinput_oleh',
        'diperiksa_oleh',
        'tanggal_pemeriksaan',
        'catatan_pemeriksaan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'tanggal_pemeriksaan' => 'datetime',
            'jumlah_liter' => 'decimal:2',
            'harga_per_liter' => 'decimal:2',
            'total_biaya' => 'decimal:2',
        ];
    }

    public static function jenisTransaksi(): array
    {
        return [
            self::STOK_AWAL => 'Stok Awal (+)',
            self::PENGAJUAN => 'Pengajuan BBM',
            self::PENERIMAAN => 'Beli / Stok Masuk (+)',
            self::PENGGUNAAN => 'Pemakaian BBM (-)',
            self::PENYESUAIAN => 'Koreksi Stok (±)',
        ];
    }

    public static function statusVerifikasi(): array
    {
        return [
            self::MENUNGGU_PEMERIKSAAN => 'Menunggu Verifikasi',
            self::TERVERIFIKASI => 'Terverifikasi',
            self::PERLU_PERBAIKAN => 'Perlu Perbaikan',
            self::DITOLAK => 'Ditolak',
        ];
    }

    public static function daftarKeperluan(): array
    {
        return [
            'operasional_drone' => 'Operasional Drone',
            'genset' => 'Genset Lapangan',
            'mobil' => 'Mobil Operasional Tim',
            'stok_masuk' => 'Stok BBM Masuk',
            'lainnya' => 'Lainnya',
        ];
    }

    public function labelKeperluan(): string
    {
        if (! $this->keperluan) {
            return '—';
        }

        return self::daftarKeperluan()[$this->keperluan] ?? ucwords(str_replace('_', ' ', $this->keperluan));
    }

    public function tim(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_id');
    }

    public function jenisBbm(): BelongsTo
    {
        return $this->belongsTo(JenisBbm::class, 'jenis_bbm_id');
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diinput_oleh');
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diperiksa_oleh');
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(LampiranBbm::class, 'transaksi_bbm_id');
    }

    public function laporanRealisasi(): BelongsTo
    {
        return $this->belongsTo(LaporanRealisasi::class, 'laporan_realisasi_id');
    }
}
