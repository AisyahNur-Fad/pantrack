<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan pengeluaran operasional kas dan bon unit drone (tabel `pengeluaran_operasional_unit`).
 * Khusus dikelola oleh Admin untuk pertanggungjawaban unit operasional.
 */
class PengeluaranOperasionalUnit extends Model
{
    public const OPERASIONAL_LAPANGAN = 'operasional_lapangan';
    public const SPAREPART = 'sparepart';
    public const RANSUM_KANTIN = 'ransum_kantin';
    public const RENTAL_MOBIL = 'rental_mobil';
    public const UPAH_LANGSIR = 'upah_langsir';
    public const BON_PRIBADI = 'bon_pribadi';
    public const LAIN_LAIN = 'lain_lain';

    protected $table = 'pengeluaran_operasional_unit';

    protected $fillable = [
        'tim_id',
        'periode_bulan',
        'periode_tahun',
        'tanggal_transaksi',
        'kategori_biaya',
        'keterangan',
        'nominal',
        'nomor_bukti_pi',
        'lampiran_nota',
        'diinput_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'nominal' => 'decimal:2',
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
        ];
    }

    public static function kategoriBiaya(): array
    {
        return [
            self::OPERASIONAL_LAPANGAN => 'Operasional Lapangan (Solar, Belanja Logistik, Antar Drone)',
            self::SPAREPART => 'Sparepart & Suku Cadang Drone (Nomor PI)',
            self::RANSUM_KANTIN => 'Bon Ransum Katering & Kantin Distrik',
            self::RENTAL_MOBIL => 'Rental Mobil Operasional Tim',
            self::UPAH_LANGSIR => 'Upah Langsir Pupuk/Pestisida',
            self::BON_PRIBADI => 'Bon Keperluan Pribadi Owner/Terkait',
            self::LAIN_LAIN => 'Lain-lain',
        ];
    }

    public function tim(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_id');
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diinput_oleh');
    }
}
