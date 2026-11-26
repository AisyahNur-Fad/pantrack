<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jenis BBM (tabel `jenis_bbm`). */
class JenisBbm extends Model
{
    protected $table = 'jenis_bbm';

    protected $fillable = ['nama_bbm', 'harga_per_liter', 'satuan', 'aktif'];

    protected function casts(): array
    {
        return [
            'harga_per_liter' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiBbm::class, 'jenis_bbm_id');
    }
}
