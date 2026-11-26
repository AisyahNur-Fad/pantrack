<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaiki identitas armada drone sesuai pembacaan tepat:
     * - Vendor: PAB DL, ESJB, CSP, dll.
     * - Nomor Drone: 601, 903, 706, 809, dll. (nomor unik)
     * - Tipe Drone: T70P, T50, T25P, T25, T20P, T55
     */
    public function up(): void
    {
        $waktu = now();

        $daftar33Drone = [
            [
                'old_nomor' => 'PAB-DF-903',
                'nomor_drone' => '903',
                'nama_drone' => 'Drone 903 (PAB-DF)',
                'tipe_drone' => 'T20P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-DF | On Duty: Standby / Cadangan',
            ],
            [
                'old_nomor' => 'PAB-DF-904',
                'nomor_drone' => '904',
                'nama_drone' => 'Drone 904 (PAB-DF)',
                'tipe_drone' => 'T25',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-DF | Helper: Irawan - Saputra | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-DF-909',
                'nomor_drone' => '909',
                'nama_drone' => 'Drone 909 (PAB-DF)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-DF | Pilot: Sofian | Helper: Ibnu - Abdul | On Duty: Distrik DKL (SBA)',
            ],
            [
                'old_nomor' => 'PAB-DF-900',
                'nomor_drone' => '900',
                'nama_drone' => 'Drone 900 (PAB-DF)',
                'tipe_drone' => 'T70P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-DF | Pilot: Wendy | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-YD-701',
                'nomor_drone' => '701',
                'nama_drone' => 'Drone 701 (PAB-YD)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-YD | Pilot: Pranoto | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-YD-704',
                'nomor_drone' => '704',
                'nama_drone' => 'Drone 704 (PAB-YD)',
                'tipe_drone' => 'T25P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-YD | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-YD-707',
                'nomor_drone' => '707',
                'nama_drone' => 'Drone 707 (PAB-YD)',
                'tipe_drone' => 'T70P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-YD | Pilot: Itsna | Helper: Irawan - Saputra | On Duty: Distrik DTP (SBA)',
            ],
            [
                'old_nomor' => 'PAB-KNS-711',
                'nomor_drone' => '711',
                'nama_drone' => 'Drone 711 (PAB-KNS)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-KNS | Pilot: Jeksen | Helper: Dio - Aldi - Naser | On Duty: Distrik DTJ (BAP)',
            ],
            [
                'old_nomor' => 'PAB-R3P-706',
                'nomor_drone' => '706',
                'nama_drone' => 'Drone 706 (PAB-R3P)',
                'tipe_drone' => 'T25P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-R3P | Pilot: Adit | Co-Pilot: Rido | On Duty: Distrik DSB (BMH)',
            ],
            [
                'old_nomor' => 'PAB-CMC-905',
                'nomor_drone' => '905',
                'nama_drone' => 'Drone 905 (PAB-CMC)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-CMC | On Duty: Standby',
            ],
            [
                'old_nomor' => 'APL-ABD-605',
                'nomor_drone' => '605',
                'nama_drone' => 'Drone 605 (APL-ABD)',
                'tipe_drone' => 'T25P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: APL-ABD | Pilot: Rico | Co-Pilot: Teguh | On Duty: Distrik DLH (SBA)',
            ],
            [
                'old_nomor' => 'PAB-DS-808',
                'nomor_drone' => '808',
                'nama_drone' => 'Drone 808 (PAB-DS)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-DS | Pilot: Anca | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-EKA-609',
                'nomor_drone' => '609',
                'nama_drone' => 'Drone 609 (PAB EKA)',
                'tipe_drone' => 'T55',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB EKA | On Duty: Distrik DTP (SBA)',
            ],
            [
                'old_nomor' => 'PAB-RH-801',
                'nomor_drone' => '801',
                'nama_drone' => 'Drone 801 (PAB-RH)',
                'tipe_drone' => 'T20P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-RH | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-RH-802',
                'nomor_drone' => '802',
                'nama_drone' => 'Drone 802 (PAB-RH)',
                'tipe_drone' => 'T20P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-RH | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-RS-805',
                'nomor_drone' => '805',
                'nama_drone' => 'Drone 805 (PAB-RS)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-RS | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-RS-806',
                'nomor_drone' => '806',
                'nama_drone' => 'Drone 806 (PAB-RS)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-RS | Pilot: Egi | Co-Pilot: Galang | On Duty: Distrik DST (BMH)',
            ],
            [
                'old_nomor' => 'PAB-DL-601',
                'nomor_drone' => '601',
                'nama_drone' => 'Drone 601 (PAB DL)',
                'tipe_drone' => 'T70P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB DL | Pilot: Aditya | Co-Pilot: Diwang | Helper: Mario - Medi | On Duty: Distrik DST (BMH)',
            ],
            [
                'old_nomor' => 'PAB-WK-602',
                'nomor_drone' => '602',
                'nama_drone' => 'Drone 602 (PAB-WK)',
                'tipe_drone' => 'T25',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-WK | Pilot: Andre | Co-Pilot: Haryadi | On Duty: Distrik DLH (SBA)',
            ],
            [
                'old_nomor' => 'PAB-LR-606',
                'nomor_drone' => '606',
                'nama_drone' => 'Drone 606 (PAB-LR)',
                'tipe_drone' => 'T25',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-LR | Pilot: Febri | On Duty: Distrik DSR (SBA)',
            ],
            [
                'old_nomor' => 'PAB-GK-608',
                'nomor_drone' => '608',
                'nama_drone' => 'Drone 608 (PAB-GK)',
                'tipe_drone' => 'T25P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-GK | Pilot: Melvin | Co-Pilot: Efran | On Duty: Distrik DTP (SBA)',
            ],
            [
                'old_nomor' => 'PAB-AYA-809',
                'nomor_drone' => '809',
                'nama_drone' => 'Drone 809 (PAB-AYA)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-AYA | Pilot: Putra | Co-Pilot: Bayu | Helper: Firman | On Duty: Distrik DLH (SBA)',
            ],
            [
                'old_nomor' => 'PAB-AKA-906',
                'nomor_drone' => '906',
                'nama_drone' => 'Drone 906 (PAB-AKA)',
                'tipe_drone' => 'T25P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-AKA | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-RCM-303',
                'nomor_drone' => '303',
                'nama_drone' => 'Drone 303 (PAB-RCM)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-RCM | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-RCM-302',
                'nomor_drone' => '302',
                'nama_drone' => 'Drone 302 (PAB-RCM)',
                'tipe_drone' => 'T25P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-RCM | On Duty: Standby',
            ],
            [
                'old_nomor' => 'PAB-ASE-301',
                'nomor_drone' => '301',
                'nama_drone' => 'Drone 301 (PAB-ASE)',
                'tipe_drone' => 'T25',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-ASE | Helper: Noto - Bagus - Acong | On Duty: Distrik DTK (BAP)',
            ],
            [
                'old_nomor' => 'PAB-ASE-305',
                'nomor_drone' => '305',
                'nama_drone' => 'Drone 305 (PAB-ASE)',
                'tipe_drone' => 'T70P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB-ASE | Pilot: Andre | On Duty: Standby',
            ],
            [
                'old_nomor' => 'ESJB-401',
                'nomor_drone' => '401',
                'nama_drone' => 'Drone 401 (ESJB)',
                'tipe_drone' => 'T25P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: ESJB | On Duty: Distrik DSJ (BAP)',
            ],
            [
                'old_nomor' => 'ESJB-402',
                'nomor_drone' => '402',
                'nama_drone' => 'Drone 402 (ESJB)',
                'tipe_drone' => 'T50',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: ESJB | Pilot: Zidane | On Duty: Distrik DSJ (BAP)',
            ],
            [
                'old_nomor' => 'ESJB-403',
                'nomor_drone' => '403',
                'nama_drone' => 'Drone 403 (ESJB)',
                'tipe_drone' => 'T70P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: ESJB | Pilot: Aldo | On Duty: Distrik DSJ (BAP)',
            ],
            [
                'old_nomor' => 'CSP-702',
                'nomor_drone' => '702',
                'nama_drone' => 'Drone 702 (CSP)',
                'tipe_drone' => 'T25',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: CSP | Pilot: Evan | On Duty: Standby',
            ],
            [
                'old_nomor' => 'CSP-703',
                'nomor_drone' => '703',
                'nama_drone' => 'Drone 703 (CSP)',
                'tipe_drone' => 'T25',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: CSP | Pilot: Arel | Helper: Shobirin - Samsul - Bpk Samsul | On Duty: Distrik DSR (SBA)',
            ],
            [
                'old_nomor' => 'PAB-HR-951',
                'nomor_drone' => '951',
                'nama_drone' => 'Drone 951 (PAB HR)',
                'tipe_drone' => 'T70P',
                'status_operasional' => 'aktif',
                'ambang_batas_servis_ha' => 200.00,
                'keterangan' => 'Vendor: PAB HR | Pilot: Heru | On Duty: Standby',
            ],
        ];

        $firstDroneId = null;

        foreach ($daftar33Drone as $d) {
            // Cek apakah ada record dengan nomor_drone baru atau old_nomor
            $exist = DB::table('drone')
                ->where('nomor_drone', $d['nomor_drone'])
                ->orWhere('nomor_drone', $d['old_nomor'])
                ->first();

            if ($exist) {
                DB::table('drone')->where('id', $exist->id)->update([
                    'nomor_drone' => $d['nomor_drone'],
                    'nama_drone' => $d['nama_drone'],
                    'tipe_drone' => $d['tipe_drone'],
                    'status_operasional' => $d['status_operasional'],
                    'ambang_batas_servis_ha' => $d['ambang_batas_servis_ha'],
                    'keterangan' => $d['keterangan'],
                    'updated_at' => $waktu,
                ]);
                if (! $firstDroneId) {
                    $firstDroneId = $exist->id;
                }
            } else {
                $newId = DB::table('drone')->insertGetId([
                    'nomor_drone' => $d['nomor_drone'],
                    'nama_drone' => $d['nama_drone'],
                    'tipe_drone' => $d['tipe_drone'],
                    'status_operasional' => $d['status_operasional'],
                    'ambang_batas_servis_ha' => $d['ambang_batas_servis_ha'],
                    'keterangan' => $d['keterangan'],
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
                if (! $firstDroneId) {
                    $firstDroneId = $newId;
                }
            }
        }

        $nomorDroneSah = array_column($daftar33Drone, 'nomor_drone');

        // Pastikan relasi tim aman ke drone yang sah
        if ($firstDroneId) {
            DB::table('tim')
                ->whereNotIn('drone_id', function ($query) use ($nomorDroneSah) {
                    $query->select('id')->from('drone')->whereIn('nomor_drone', $nomorDroneSah);
                })
                ->update(['drone_id' => $firstDroneId]);
        }

        // Bersihkan data jika ada nomor selain 33 unit sah
        DB::table('drone')->whereNotIn('nomor_drone', $nomorDroneSah)->delete();
    }

    public function down(): void
    {
        // Tidak perlu di-rollback karena perbaikan data valid
    }
};

