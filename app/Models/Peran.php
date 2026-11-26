<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Peran pengguna (tabel `peran`).
 * Empat peran resmi menurut PRD bab 4: admin, pekerja lapangan, kepala lapangan, atasan.
 */
class Peran extends Model
{
    public const ADMIN = 'admin';
    public const PEKERJA_LAPANGAN = 'pekerja_lapangan';
    public const KEPALA_LAPANGAN = 'kepala_lapangan';
    public const ATASAN = 'atasan';

    protected $table = 'peran';

    protected $fillable = ['nama', 'keterangan'];

    /** Label tampilan untuk setiap peran. */
    public static function labels(): array
    {
        return [
            self::ADMIN => 'Admin',
            self::PEKERJA_LAPANGAN => 'Pekerja Lapangan',
            self::KEPALA_LAPANGAN => 'Kepala Lapangan',
            self::ATASAN => 'Atasan',
        ];
    }

    public function getLabelAttribute(): string
    {
        return self::labels()[$this->nama] ?? $this->nama;
    }

    public function pengguna(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'peran_id');
    }
}
