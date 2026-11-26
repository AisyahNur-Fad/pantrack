<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Perusahaan / PT (tabel `pt`). */
class Pt extends Model
{
    protected $table = 'pt';

    protected $fillable = [
        'nama_pt',
        'kode_pt',
        'keterangan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * Relasi One-to-Many: Satu PT memiliki banyak Distrik.
     */
    public function distrik(): HasMany
    {
        return $this->hasMany(Distrik::class, 'pt_id');
    }
}

