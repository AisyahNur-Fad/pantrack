<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tim drone (tabel `tim`). Satu tim bertanggung jawab atas satu drone dan satu saldo BBM. */
class Tim extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_TIDAK_AKTIF = 'tidak_aktif';

    public const SKEMA_HELPER_PER_HEKTAR = 'per_hektar';
    public const SKEMA_HELPER_HARIAN = 'harian';

    protected $table = 'tim';

    protected $fillable = [
        'nama_tim',
        'drone_id',
        'distrik_id',
        'kepala_tim_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_tim',
        'premi_pilot_per_ha',
        'premi_helper_per_ha',
        'skema_upah_helper',
        'tarif_helper_per_hari',
        'gaji_pokok_pilot',
        'gaji_pokok_helper',
        'uang_makan_pilot',
        'uang_makan_helper_per_hari',
        'biaya_enterteint_per_ha',
        'honor_adm_tim',
        'biaya_mapping_per_ha',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'premi_pilot_per_ha' => 'decimal:2',
            'premi_helper_per_ha' => 'decimal:2',
            'tarif_helper_per_hari' => 'decimal:2',
            'gaji_pokok_pilot' => 'decimal:2',
            'gaji_pokok_helper' => 'decimal:2',
            'uang_makan_pilot' => 'decimal:2',
            'uang_makan_helper_per_hari' => 'decimal:2',
            'biaya_enterteint_per_ha' => 'decimal:2',
            'honor_adm_tim' => 'decimal:2',
            'biaya_mapping_per_ha' => 'decimal:2',
        ];
    }

    public static function statusTim(): array
    {
        return [
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_TIDAK_AKTIF => 'Tidak aktif',
        ];
    }

    public static function skemaUpahHelper(): array
    {
        return [
            self::SKEMA_HELPER_PER_HEKTAR => 'Per Hektar (Borongan Luasan)',
            self::SKEMA_HELPER_HARIAN => 'Harian (Hari Kerja Aktif)',
        ];
    }

    public function getLabelSkemaUpahHelperAttribute(): string
    {
        return self::skemaUpahHelper()[$this->skema_upah_helper] ?? 'Per Hektar (Borongan Luasan)';
    }

    public function drone(): BelongsTo
    {
        return $this->belongsTo(Drone::class, 'drone_id');
    }

    public function distrik(): BelongsTo
    {
        return $this->belongsTo(Distrik::class, 'distrik_id');
    }

    public function kepalaTim(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'kepala_tim_id');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaTim::class, 'tim_id');
    }

    public function transaksiBbm(): HasMany
    {
        return $this->hasMany(TransaksiBbm::class, 'tim_id');
    }

    public function laporanRealisasi(): HasMany
    {
        return $this->hasMany(LaporanRealisasi::class, 'tim_id');
    }

    public function pengeluaranOperasional(): HasMany
    {
        return $this->hasMany(PengeluaranOperasionalUnit::class, 'tim_id');
    }

    public function kasbonPersonil(): HasMany
    {
        return $this->hasMany(KasbonPersonil::class, 'tim_id');
    }
}
