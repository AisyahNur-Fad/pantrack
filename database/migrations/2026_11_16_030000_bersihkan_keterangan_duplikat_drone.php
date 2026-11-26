<?php

use App\Models\Drone;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Bersihkan kolom keterangan pada master drone yang masih berisikan teks
     * duplikasi Vendor, Pilot, dan On Duty.
     * Pilot & On Duty kini sudah resmi berada di tabel Tim & Anggota Tim,
     * serta Vendor sudah tertera pada Nama Drone.
     */
    public function up(): void
    {
        Drone::where('keterangan', 'like', 'Vendor:%')
            ->orWhere('keterangan', 'Catatan update')
            ->update([
                'keterangan' => null,
            ]);
    }

    public function down(): void
    {
        //
    }
};

