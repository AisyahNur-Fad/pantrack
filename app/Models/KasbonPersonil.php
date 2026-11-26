<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan penarikan kasbon cash personil tim (tabel `kasbon_personil`).
 * Khusus dikelola oleh Admin untuk memotong slip gaji kru secara otomatis.
 */
class KasbonPersonil extends Model
{
    public const PILOT = 'pilot';
    public const HELPER = 'helper';
    public const PELANGSIR = 'pelangsir';
    public const LAINNYA = 'lainnya';

    protected $table = 'kasbon_personil';

    protected $fillable = [
        'tim_id',
        'nama_personil',
        'peran_kru',
        'tanggal_kasbon',
        'periode_bulan',
        'periode_tahun',
        'jumlah_kasbon',
        'keterangan',
        'status_lunas',
        'diinput_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kasbon' => 'date',
            'jumlah_kasbon' => 'decimal:2',
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
            'status_lunas' => 'boolean',
        ];
    }

    public static function peranKru(): array
    {
        return [
            self::PILOT => 'Pilot Drone',
            self::HELPER => 'Helper / Pembantu Lapangan',
            self::PELANGSIR => 'Pelangsir / Transport',
            self::LAINNYA => 'Kru Lainnya',
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
