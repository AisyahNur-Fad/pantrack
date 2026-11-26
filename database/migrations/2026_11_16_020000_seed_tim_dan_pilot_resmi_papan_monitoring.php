<?php

use App\Models\AnggotaTim;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tim;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Daftarkan tim lapangan resmi, pilot, co-pilot, dan helper dari papan monitoring (Opsi A):
     * Setiap tim mengikat:
     * - 1 Unit Drone
     * - 1 Distrik Basis On Duty
     * - Pilot (Kepala Tim / User Pekerja)
     * - Co-Pilot & Helper
     */
    public function up(): void
    {
        $peranPekerja = Peran::where('nama', Peran::PEKERJA_LAPANGAN)->first();
        if (! $peranPekerja) {
            return;
        }

        $waktu = now();
        $kataSandi = Hash::make('password');

        // Daftar 17 Tim On Duty dari Papan Monitoring
        $daftarTimPapan = [
            // 1. PT BMH - Distrik DST (2 Drone: 601 & 806)
            [
                'nomor_drone' => '601',
                'nama_distrik' => 'Distrik DST',
                'nama_tim' => 'Tim DST - Drone 601 (Aditya)',
                'pilot' => 'Aditya',
                'co_pilot' => 'Diwang',
                'helper' => 'Mario - Medi',
            ],
            [
                'nomor_drone' => '806',
                'nama_distrik' => 'Distrik DST',
                'nama_tim' => 'Tim DST - Drone 806 (Egi)',
                'pilot' => 'Egi',
                'co_pilot' => 'Galang',
                'helper' => null,
            ],
            // 2. PT BMH - Distrik DSB (1 Drone: 706)
            [
                'nomor_drone' => '706',
                'nama_distrik' => 'Distrik DSB',
                'nama_tim' => 'Tim DSB - Drone 706 (Adit)',
                'pilot' => 'Adit',
                'co_pilot' => 'Rido',
                'helper' => null,
            ],
            // 3. PT BAP - Distrik DSJ (3 Drone: 401, 402, 403)
            [
                'nomor_drone' => '401',
                'nama_distrik' => 'Distrik DSJ',
                'nama_tim' => 'Tim DSJ - Drone 401 (ESJB)',
                'pilot' => 'Kru ESJB 401',
                'co_pilot' => null,
                'helper' => null,
            ],
            [
                'nomor_drone' => '402',
                'nama_distrik' => 'Distrik DSJ',
                'nama_tim' => 'Tim DSJ - Drone 402 (Zidane)',
                'pilot' => 'Zidane',
                'co_pilot' => null,
                'helper' => null,
            ],
            [
                'nomor_drone' => '403',
                'nama_distrik' => 'Distrik DSJ',
                'nama_tim' => 'Tim DSJ - Drone 403 (Aldo)',
                'pilot' => 'Aldo',
                'co_pilot' => null,
                'helper' => null,
            ],
            // 4. PT BAP - Distrik DTJ (1 Drone: 711)
            [
                'nomor_drone' => '711',
                'nama_distrik' => 'Distrik DTJ',
                'nama_tim' => 'Tim DTJ - Drone 711 (Jeksen)',
                'pilot' => 'Jeksen',
                'co_pilot' => null,
                'helper' => 'Dio - Aldi - Naser',
            ],
            // 5. PT BAP - Distrik DTK (1 Drone: 301)
            [
                'nomor_drone' => '301',
                'nama_distrik' => 'Distrik DTK',
                'nama_tim' => 'Tim DTK - Drone 301 (PAB-ASE)',
                'pilot' => 'Kru ASE 301',
                'co_pilot' => null,
                'helper' => 'Noto - Bagus - Acong',
            ],
            // 6. PT SBA - Distrik DLH (3 Drone: 605, 602, 809)
            [
                'nomor_drone' => '605',
                'nama_distrik' => 'Distrik DLH',
                'nama_tim' => 'Tim DLH - Drone 605 (Rico)',
                'pilot' => 'Rico',
                'co_pilot' => 'Teguh',
                'helper' => null,
            ],
            [
                'nomor_drone' => '602',
                'nama_distrik' => 'Distrik DLH',
                'nama_tim' => 'Tim DLH - Drone 602 (Andre)',
                'pilot' => 'Andre (DLH)',
                'co_pilot' => 'Haryadi',
                'helper' => null,
            ],
            [
                'nomor_drone' => '809',
                'nama_distrik' => 'Distrik DLH',
                'nama_tim' => 'Tim DLH - Drone 809 (Putra)',
                'pilot' => 'Putra',
                'co_pilot' => 'Bayu',
                'helper' => 'Firman',
            ],
            // 7. PT SBA - Distrik DTP (3 Drone: 707, 608, 609)
            [
                'nomor_drone' => '707',
                'nama_distrik' => 'Distrik DTP',
                'nama_tim' => 'Tim DTP - Drone 707 (Itsna)',
                'pilot' => 'Itsna',
                'co_pilot' => null,
                'helper' => 'Irawan - Saputra',
            ],
            [
                'nomor_drone' => '608',
                'nama_distrik' => 'Distrik DTP',
                'nama_tim' => 'Tim DTP - Drone 608 (Melvin)',
                'pilot' => 'Melvin',
                'co_pilot' => 'Efran',
                'helper' => null,
            ],
            [
                'nomor_drone' => '609',
                'nama_distrik' => 'Distrik DTP',
                'nama_tim' => 'Tim DTP - Drone 609 (PAB EKA)',
                'pilot' => 'Kru EKA 609',
                'co_pilot' => null,
                'helper' => null,
            ],
            // 8. PT SBA - Distrik DKL (1 Drone: 909)
            [
                'nomor_drone' => '909',
                'nama_distrik' => 'Distrik DKL',
                'nama_tim' => 'Tim DKL - Drone 909 (Sofian)',
                'pilot' => 'Sofian',
                'co_pilot' => null,
                'helper' => 'Ibnu - Abdul',
            ],
            // 9. PT SBA - Distrik DSR (2 Drone: 606 & 703)
            [
                'nomor_drone' => '606',
                'nama_distrik' => 'Distrik DSR',
                'nama_tim' => 'Tim DSR - Drone 606 (Febri)',
                'pilot' => 'Febri',
                'co_pilot' => null,
                'helper' => null,
            ],
            [
                'nomor_drone' => '703',
                'nama_distrik' => 'Distrik DSR',
                'nama_tim' => 'Tim DSR - Drone 703 (Arel)',
                'pilot' => 'Arel',
                'co_pilot' => null,
                'helper' => 'Shobirin - Samsul - Bpk Samsul',
            ],
        ];

        foreach ($daftarTimPapan as $index => $item) {
            $drone = Drone::where('nomor_drone', $item['nomor_drone'])->first();
            $distrik = Distrik::where('nama_distrik', $item['nama_distrik'])->first();

            if (! $drone || ! $distrik) {
                continue;
            }

            // Buat akun pengguna untuk Pilot jika ada
            $pilotUser = null;
            if ($item['pilot']) {
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $item['pilot']));
                if (strlen($username) < 3) {
                    $username = 'pilot' . $item['nomor_drone'];
                }

                $pilotUser = Pengguna::firstOrCreate(
                    ['nama_pengguna' => $username],
                    [
                        'peran_id' => $peranPekerja->id,
                        'nama_lengkap' => $item['pilot'] . ' (Pilot)',
                        'email' => $username . '@pantrack.local',
                        'kata_sandi' => $kataSandi,
                        'nomor_telepon' => '081234' . str_pad((string) ($index + 10), 6, '0', STR_PAD_LEFT),
                        'aktif' => true,
                    ]
                );
            }

            // Buat / Update Tim Lapangan
            $tim = Tim::where('drone_id', $drone->id)->first();
            if ($tim) {
                $tim->update([
                    'nama_tim' => $item['nama_tim'],
                    'distrik_id' => $distrik->id,
                    'kepala_tim_id' => $pilotUser?->id,
                    'status_tim' => Tim::STATUS_AKTIF,
                    'updated_at' => $waktu,
                ]);
            } else {
                $tim = Tim::create([
                    'nama_tim' => $item['nama_tim'],
                    'drone_id' => $drone->id,
                    'distrik_id' => $distrik->id,
                    'kepala_tim_id' => $pilotUser?->id,
                    'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    'status_tim' => Tim::STATUS_AKTIF,
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
            }

            // Daftarkan personil tim ke anggota_tim
            if ($pilotUser) {
                AnggotaTim::firstOrCreate(
                    ['tim_id' => $tim->id, 'jabatan_tim' => AnggotaTim::PILOT],
                    [
                        'pengguna_id' => $pilotUser->id,
                        'nama_anggota' => $item['pilot'],
                        'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    ]
                );
            }

            if ($item['co_pilot']) {
                AnggotaTim::firstOrCreate(
                    ['tim_id' => $tim->id, 'jabatan_tim' => AnggotaTim::CO_PILOT],
                    [
                        'pengguna_id' => null,
                        'nama_anggota' => $item['co_pilot'],
                        'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    ]
                );
            }

            if ($item['helper']) {
                AnggotaTim::firstOrCreate(
                    ['tim_id' => $tim->id, 'jabatan_tim' => AnggotaTim::HELPER],
                    [
                        'pengguna_id' => null,
                        'nama_anggota' => $item['helper'],
                        'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // ...
    }
};

