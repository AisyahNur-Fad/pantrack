<?php

namespace App\Services;

use App\Models\RiwayatAktivitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Satu-satunya pintu untuk menulis riwayat aktivitas (H. Riwayat Aktivitas pada PRD).
 * Semua controller memanggil service ini, sehingga format catatan konsisten.
 */
class RiwayatAktivitasService
{
    /**
     * @param  string  $aktivitas  contoh: "Login", "Menambah data Distrik"
     * @param  Model|string  $data  model yang dikenai aktivitas, atau nama jenis data bila tidak ada modelnya
     */
    public function catat(string $aktivitas, Model|string $data, ?string $keterangan = null): RiwayatAktivitas
    {
        [$jenisData, $idData] = $data instanceof Model
            ? [$data->getTable(), $data->getKey()]
            : [$data, null];

        return RiwayatAktivitas::create([
            'pengguna_id' => Auth::id(),
            'nama_aktivitas' => Str::limit($aktivitas, 120, ''),
            'jenis_data' => $jenisData,
            'id_data' => $idData,
            'keterangan' => $keterangan,
            'alamat_ip' => request()->ip(),
        ]);
    }
}
