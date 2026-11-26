<?php

use App\Models\Drone;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Ubah isi kolom nama_drone menjadi murni nama vendor pemilik/penyedia armada drone
     * agar tidak terjadi pengulangan (nomor drone sudah ada di kolom nomor_drone terpisah).
     */
    public function up(): void
    {
        $vendorMap = [
            '903' => 'PAB-DF',
            '904' => 'PAB-DF',
            '909' => 'PAB-DF',
            '900' => 'PAB-DF',
            '701' => 'PAB-YD',
            '704' => 'PAB-YD',
            '707' => 'PAB-YD',
            '711' => 'PAB-KNS',
            '706' => 'PAB-R3P',
            '905' => 'PAB-CMC',
            '605' => 'APL-ABD',
            '808' => 'PAB-DS',
            '609' => 'PAB EKA',
            '801' => 'PAB-RH',
            '802' => 'PAB-RH',
            '805' => 'PAB-RS',
            '806' => 'PAB-RS',
            '601' => 'PAB DL',
            '602' => 'PAB-WK',
            '606' => 'PAB-LR',
            '608' => 'PAB-GK',
            '809' => 'PAB-AYA',
            '906' => 'PAB-AKA',
            '303' => 'PAB-RCM',
            '302' => 'PAB-RCM',
            '301' => 'PAB-ASE',
            '305' => 'PAB-ASE',
            '401' => 'ESJB',
            '402' => 'ESJB',
            '403' => 'ESJB',
            '702' => 'CSP',
            '703' => 'CSP',
            '951' => 'PAB HR',
        ];

        foreach ($vendorMap as $nomor => $vendor) {
            Drone::where('nomor_drone', $nomor)->update([
                'nama_drone' => $vendor,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};

