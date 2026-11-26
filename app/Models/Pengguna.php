<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Pengguna aplikasi (tabel `pengguna`), menggantikan model User bawaan Laravel.
 * Kolom kata sandi bernama `kata_sandi` dan tabel tidak memiliki `remember_token`.
 */
class Pengguna extends Authenticatable
{
    protected $table = 'pengguna';

    protected $fillable = [
        'peran_id',
        'nama_lengkap',
        'nama_pengguna',
        'email',
        'kata_sandi',
        'nomor_telepon',
        'aktif',
    ];

    protected $hidden = ['kata_sandi'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'kata_sandi' => 'hashed',
        ];
    }

    /* ------------------------------------------------------------------
     | Kontrak autentikasi
     | ------------------------------------------------------------------ */

    public function getAuthPassword(): string
    {
        return $this->kata_sandi;
    }

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    // Tabel `pengguna` tidak memiliki kolom remember_token, fitur "ingat saya" dinonaktifkan.
    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // sengaja kosong
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    /* ------------------------------------------------------------------
     | Relasi
     | ------------------------------------------------------------------ */

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'peran_id');
    }

    public function keanggotaanTim(): HasMany
    {
        return $this->hasMany(AnggotaTim::class, 'pengguna_id');
    }

    public function timYangDipimpin(): HasMany
    {
        return $this->hasMany(Tim::class, 'kepala_tim_id');
    }

    public function riwayatAktivitas(): HasMany
    {
        return $this->hasMany(RiwayatAktivitas::class, 'pengguna_id');
    }

    /* ------------------------------------------------------------------
     | Hak akses
     | ------------------------------------------------------------------ */

    public function punyaPeran(string ...$peran): bool
    {
        return in_array($this->peran?->nama, $peran, true);
    }

    public function adalahAdmin(): bool
    {
        return $this->punyaPeran(Peran::ADMIN);
    }

    public function adalahPekerjaLapangan(): bool
    {
        return $this->punyaPeran(Peran::PEKERJA_LAPANGAN);
    }

    public function adalahKepalaLapangan(): bool
    {
        return $this->punyaPeran(Peran::KEPALA_LAPANGAN);
    }

    public function adalahAtasan(): bool
    {
        return $this->punyaPeran(Peran::ATASAN);
    }

    public function getLabelPeranAttribute(): string
    {
        return $this->peran?->label ?? '-';
    }

    /** Dua huruf awal nama untuk avatar, contoh "Budi Santoso" menjadi "BS". */
    public function getInisialAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->nama_lengkap)))
            ->take(2)
            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode('');
    }

    /**
     * Daftar id tim yang datanya boleh dilihat pengguna ini.
     *
     * - Pekerja lapangan: hanya tim tempat ia menjadi anggota aktif atau penanggung jawab ("sesuai tim").
     * - Admin, kepala lapangan, dan atasan: semua tim (null berarti tanpa batasan).
     *   Batas "wilayah pengawasan" kepala lapangan belum ditetapkan pada PRD sehingga
     *   aturannya cukup diubah di satu tempat ini setelah dikonfirmasi perusahaan.
     *
     * @return array<int>|null
     */
    public function idTimYangDiakses(): ?array
    {
        if (! $this->adalahPekerjaLapangan()) {
            return null;
        }

        $hariIni = now()->toDateString();

        $sebagaiAnggota = $this->keanggotaanTim()
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $hariIni))
            ->pluck('tim_id');

        $sebagaiPenanggungJawab = $this->timYangDipimpin()->pluck('id');

        return $sebagaiAnggota->merge($sebagaiPenanggungJawab)->unique()->values()->all();
    }
}
