<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anggota tim (tabel `anggota_tim`).
 * `pengguna_id` hanya diisi bila anggota memiliki akun; helper boleh berupa nama bebas (BR-06).
 */
class AnggotaTim extends Model
{
    public const PILOT = 'pilot';
    public const CO_PILOT = 'co_pilot';
    public const HELPER = 'helper';
    public const ANGGOTA_LAIN = 'anggota_lain';

    protected $table = 'anggota_tim';

    protected $fillable = [
        'tim_id',
        'pengguna_id',
        'nama_anggota',
        'jabatan_tim',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public static function jabatanTim(): array
    {
        return [
            self::PILOT => 'Pilot',
            self::CO_PILOT => 'Co-pilot',
            self::HELPER => 'Helper',
            self::ANGGOTA_LAIN => 'Anggota lain',
        ];
    }

    public function tim(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
