<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Detail petak pada SPK beserta luas target (tabel `detail_spk`). */
class DetailSpk extends Model
{
    protected $table = 'detail_spk';

    protected $fillable = [
        'spk_id',
        'petak_id',
        'luas_target_ha',
        'tarif_per_ha',
        'perlu_mapping',
        'tarif_mapping_per_ha',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'luas_target_ha' => 'decimal:2',
            'tarif_per_ha' => 'decimal:2',
            'perlu_mapping' => 'boolean',
            'tarif_mapping_per_ha' => 'decimal:2',
        ];
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'spk_id');
    }

    public function petak(): BelongsTo
    {
        return $this->belongsTo(Petak::class, 'petak_id');
    }

    /**
     * Estimasi subtotal biaya pekerjaan pokok pada petak ini.
     */
    public function getSubtotalPokokAttribute(): float
    {
        return (float) ($this->luas_target_ha * ($this->tarif_per_ha ?? 0));
    }

    /**
     * Estimasi subtotal biaya mapping jika petak ini memerlukan jasa pemetaan drone.
     */
    public function getSubtotalMappingAttribute(): float
    {
        return $this->perlu_mapping ? (float) ($this->luas_target_ha * ($this->tarif_mapping_per_ha ?? 0)) : 0.0;
    }

    /**
     * Estimasi total gabungan biaya (Pokok + Mapping) untuk petak ini.
     */
    public function getSubtotalBiayaAttribute(): float
    {
        return $this->subtotal_pokok + $this->subtotal_mapping;
    }
}
