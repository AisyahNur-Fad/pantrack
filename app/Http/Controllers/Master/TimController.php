<?php

namespace App\Http\Controllers\Master;

use App\Models\AnggotaTim;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Tim::class;
    }

    protected function judul(): string
    {
        return 'Tim';
    }

    protected function prefixRute(): string
    {
        return 'master.tim';
    }

    protected function ikon(): string
    {
        return 'bi-people';
    }

    protected function deskripsi(): string
    {
        return 'Tim drone. Setiap tim memiliki satu drone dan saldo BBM sendiri.';
    }

    protected function relasi(): array
    {
        return ['drone', 'distrik', 'kepalaTim'];
    }

    protected function pencarian(): array
    {
        return ['nama_tim'];
    }

    protected function urutan(): array
    {
        return ['nama_tim' => 'asc'];
    }

    protected function filter(): array
    {
        return [
            ['name' => 'distrik_id', 'label' => 'Semua distrik', 'options' => fn () => Distrik::orderBy('nama_distrik')->pluck('nama_distrik', 'id')->all()],
            ['name' => 'status_tim', 'label' => 'Semua status', 'options' => Tim::statusTim()],
        ];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Nama tim', 'key' => 'nama_tim'],
            ['label' => 'Drone', 'key' => 'drone.nomor_drone'],
            ['label' => 'Distrik', 'key' => 'distrik.nama_distrik'],
            ['label' => 'Pilot Penanggung Jawab', 'key' => 'kepalaTim.nama_lengkap'],
            ['label' => 'Skema Helper', 'key' => 'label_skema_upah_helper'],
            ['label' => 'Mulai', 'key' => 'tanggal_mulai', 'tipe' => 'tanggal'],
            ['label' => 'Status', 'key' => 'status_tim', 'tipe' => 'badge', 'opsi' => Tim::statusTim(), 'badge' => [Tim::STATUS_AKTIF => 'success', Tim::STATUS_TIDAK_AKTIF => 'netral']],
        ];
    }

    public function index(Request $request): View
    {
        $response = parent::index($request);
        $data = $response->getData();

        $totalTim = Tim::count();
        $totalAktif = Tim::where('status_tim', Tim::STATUS_AKTIF)->count();
        $totalTidakAktif = Tim::where('status_tim', Tim::STATUS_TIDAK_AKTIF)->count();
        $totalDroneTerhubung = Tim::where('status_tim', Tim::STATUS_AKTIF)->whereNotNull('drone_id')->distinct('drone_id')->count();
        $totalPilotBertugas = Tim::where('status_tim', Tim::STATUS_AKTIF)->whereNotNull('kepala_tim_id')->distinct('kepala_tim_id')->count();

        $data['ringkasanTim'] = [
            'total' => $totalTim,
            'aktif' => $totalAktif,
            'tidak_aktif' => $totalTidakAktif,
            'drone_terhubung' => $totalDroneTerhubung,
            'pilot_bertugas' => $totalPilotBertugas,
        ];

        return view('master.index', $data);
    }

    protected function field(): array
    {
        return [
            ['name' => 'nama_tim', 'label' => 'Nama tim', 'tipe' => 'teks', 'wajib' => true],
            [
                'name' => 'drone_id', 'label' => 'Drone', 'tipe' => 'pilih', 'wajib' => true,
                'options' => fn () => Drone::orderBy('nomor_drone')->get()->mapWithKeys(function ($d) {
                    $prefix = '';
                    if ($d->status_operasional === Drone::STATUS_PERAWATAN) {
                        $prefix = '[PERAWATAN] ';
                    } elseif ($d->status_operasional === Drone::STATUS_TIDAK_BEROPERASI) {
                        $prefix = '[NONAKTIF] ';
                    }
                    return [$d->id => $prefix.$d->nomor_drone.($d->nama_drone ? ' · '.$d->nama_drone : '').' ('.$d->tipe_drone.')'];
                })->all(),
                'bantuan' => 'Satu drone hanya boleh dikaitkan dengan satu tim aktif.',
            ],
            [
                'name' => 'distrik_id', 'label' => 'Distrik', 'tipe' => 'pilih', 'kosong' => 'Belum ditentukan',
                'options' => fn () => Distrik::orderBy('nama_distrik')->pluck('nama_distrik', 'id')->all(),
            ],
            [
                'name' => 'kepala_tim_id',
                'label' => 'Pilot Penanggung Jawab Drone',
                'tipe' => 'pilih',
                'kosong' => 'Belum ditentukan',
                'options' => function () {
                    // Hanya menarik akun aktif dengan peran Pekerja Lapangan (Pilot)
                    $daftarPilot = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', Peran::PEKERJA_LAPANGAN))
                        ->where('aktif', true)
                        ->orderBy('nama_lengkap')
                        ->get();

                    $timAktifPerPilot = Tim::where('status_tim', Tim::STATUS_AKTIF)
                        ->whereNotNull('kepala_tim_id')
                        ->with('drone')
                        ->get()
                        ->keyBy('kepala_tim_id');

                    return $daftarPilot->mapWithKeys(function ($p) use ($timAktifPerPilot) {
                        $tim = $timAktifPerPilot->get($p->id);
                        $statusTugas = $tim
                            ? ' · [Bertugas: '.$tim->nama_tim.' · Drone '.($tim->drone?->nomor_drone ?? '-').']'
                            : ' · [Tersedia / Siap Tugas]';

                        return [$p->id => $p->nama_lengkap.$statusTugas];
                    })->all();
                },
                'bantuan' => 'Hanya menampilkan akun Pilot (Pekerja Lapangan). 1 Drone hanya dapat dipertanggungjawabkan oleh 1 Pilot aktif.',
            ],
            ['name' => 'tanggal_mulai', 'label' => 'Tanggal mulai', 'tipe' => 'tanggal'],
            ['name' => 'tanggal_selesai', 'label' => 'Tanggal selesai', 'tipe' => 'tanggal'],
            ['name' => 'status_tim', 'label' => 'Status tim', 'tipe' => 'pilih', 'options' => Tim::statusTim(), 'wajib' => true, 'default' => Tim::STATUS_AKTIF],
            ['name' => 'gaji_pokok_pilot', 'label' => 'Gaji Pokok Pilot Bulanan (Rp)', 'tipe' => 'angka', 'default' => 1000000, 'bantuan' => 'Gaji tetap bulanan untuk pilot.'],
            ['name' => 'premi_pilot_per_ha', 'label' => 'Premi Pilot per Ha (Rp)', 'tipe' => 'angka', 'default' => 22000, 'bantuan' => 'Insentif premi terbang pilot per hektar (contoh: 22000).'],
            ['name' => 'uang_makan_pilot', 'label' => 'Uang Makan Pilot Bulanan (Rp)', 'tipe' => 'angka', 'default' => 900000, 'bantuan' => 'Uang makan bulanan tetap untuk pilot.'],
            ['name' => 'skema_upah_helper', 'label' => 'Skema Pembayaran Helper', 'tipe' => 'pilih', 'options' => Tim::skemaUpahHelper(), 'default' => Tim::SKEMA_HELPER_PER_HEKTAR, 'wajib' => true, 'bantuan' => 'Pilih apakah helper dibayar borongan per hektar atau upah harian kerja.'],
            ['name' => 'premi_helper_per_ha', 'label' => 'Tarif Helper per Ha (Rp)', 'tipe' => 'angka', 'default' => 10000, 'bantuan' => 'Nominal upah/insentif helper per hektar (aktif jika skema Per Hektar dipilih).'],
            ['name' => 'tarif_helper_per_hari', 'label' => 'Tarif Helper per Hari (Rp)', 'tipe' => 'angka', 'default' => 100000, 'bantuan' => 'Nominal upah harian helper (aktif jika skema Harian dipilih).'],
            ['name' => 'gaji_pokok_helper', 'label' => 'Gaji Pokok Helper Bulanan (Rp)', 'tipe' => 'angka', 'default' => 0, 'bantuan' => 'Gaji pokok bulanan helper (default 0 jika tidak ada gaji tetap).'],
            ['name' => 'uang_makan_helper_per_hari', 'label' => 'Uang Makan Helper per Hari (Rp)', 'tipe' => 'angka', 'default' => 50000, 'bantuan' => 'Uang makan helper per hari kerja aktif.'],
            ['name' => 'biaya_mapping_per_ha', 'label' => 'Biaya Mapping per Ha (Rp)', 'tipe' => 'angka', 'default' => 30000, 'bantuan' => 'Tarif mapping per hektar.'],
            ['name' => 'biaya_enterteint_per_ha', 'label' => 'Biaya Enterteint per Ha (Rp)', 'tipe' => 'angka', 'default' => 10000, 'bantuan' => 'Biaya enterteint / koordinasi per hektar.'],
            ['name' => 'honor_adm_tim', 'label' => 'Honor ADM Tim Bulanan (Rp)', 'tipe' => 'angka', 'default' => 500000, 'bantuan' => 'Honor administrasi tim bulanan.'],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'nama_tim' => ['required', 'string', 'max:120', Rule::unique('tim', 'nama_tim')->ignore($record?->id)],
            'drone_id' => ['required', 'exists:drone,id'],
            'distrik_id' => ['nullable', 'exists:distrik,id'],
            'kepala_tim_id' => [
                'nullable',
                'exists:pengguna,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $pilot = Pengguna::with('peran')->find($value);
                        if (! $pilot || $pilot->peran?->nama !== Peran::PEKERJA_LAPANGAN) {
                            $fail('Penanggung jawab tim harus merupakan pengguna dengan peran Pilot / Pekerja Lapangan.');
                        }
                    }
                },
            ],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status_tim' => ['required', Rule::in(array_keys(Tim::statusTim()))],
            'gaji_pokok_pilot' => ['nullable', 'numeric', 'min:0'],
            'premi_pilot_per_ha' => ['nullable', 'numeric', 'min:0'],
            'uang_makan_pilot' => ['nullable', 'numeric', 'min:0'],
            'skema_upah_helper' => ['nullable', Rule::in([Tim::SKEMA_HELPER_PER_HEKTAR, Tim::SKEMA_HELPER_HARIAN])],
            'premi_helper_per_ha' => ['nullable', 'numeric', 'min:0'],
            'tarif_helper_per_hari' => ['nullable', 'numeric', 'min:0'],
            'gaji_pokok_helper' => ['nullable', 'numeric', 'min:0'],
            'uang_makan_helper_per_hari' => ['nullable', 'numeric', 'min:0'],
            'biaya_mapping_per_ha' => ['nullable', 'numeric', 'min:0'],
            'biaya_enterteint_per_ha' => ['nullable', 'numeric', 'min:0'],
            'honor_adm_tim' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** Menjaga 1 drone dan 1 pilot hanya terhubung ke satu tim aktif, serta tidak menugaskan drone dalam perawatan. */
    protected function validasiTambahan(array $data, ?Model $record): void
    {
        if ($data['status_tim'] !== Tim::STATUS_AKTIF) {
            return;
        }

        $drone = Drone::find($data['drone_id']);
        if ($drone && $drone->status_operasional === Drone::STATUS_PERAWATAN) {
            throw ValidationException::withMessages([
                'drone_id' => 'Drone '.$drone->nomor_drone.' sedang dalam status PERAWATAN'.($drone->catatan_perawatan ? ' ('.$drone->catatan_perawatan.')' : '').'. Selesaikan perawatan atau ubah status drone menjadi Aktif terlebih dahulu.',
            ]);
        }
        if ($drone && $drone->status_operasional === Drone::STATUS_TIDAK_BEROPERASI) {
            throw ValidationException::withMessages([
                'drone_id' => 'Drone '.$drone->nomor_drone.' berstatus TIDAK BEROPERASI. Silakan pilih unit drone lain yang aktif.',
            ]);
        }

        $bentrok = Tim::where('drone_id', $data['drone_id'])
            ->where('status_tim', Tim::STATUS_AKTIF)
            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
            ->first();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'drone_id' => 'Drone ini sedang digunakan tim aktif "'.$bentrok->nama_tim.'". Nonaktifkan tim tersebut terlebih dahulu.',
            ]);
        }

        if (! empty($data['kepala_tim_id']) && (! $record || (int) $record->kepala_tim_id !== (int) $data['kepala_tim_id'])) {
            $pilotBentrok = Tim::where('kepala_tim_id', $data['kepala_tim_id'])
                ->where('status_tim', Tim::STATUS_AKTIF)
                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                ->with('drone')
                ->first();

            if ($pilotBentrok) {
                $droneNomor = $pilotBentrok->drone?->nomor_drone ?? '-';
                throw ValidationException::withMessages([
                    'kepala_tim_id' => "Pilot ini sudah aktif bertanggung jawab pada unit Drone {$droneNomor} di tim '{$pilotBentrok->nama_tim}'. Satu pilot hanya dapat bertanggung jawab atas satu unit drone aktif.",
                ]);
            }
        }
    }

    /** Sinkronisasi pilot penanggung jawab tim ke tabel anggota_tim. */
    protected function setelahSimpan(Model $record, array $data, bool $isBaru): void
    {
        /** @var Tim $record */
        if (! empty($record->kepala_tim_id)) {
            $pilot = Pengguna::find($record->kepala_tim_id);
            if ($pilot) {
                AnggotaTim::updateOrCreate(
                    [
                        'tim_id' => $record->id,
                        'jabatan_tim' => AnggotaTim::PILOT,
                    ],
                    [
                        'pengguna_id' => $pilot->id,
                        'nama_anggota' => $pilot->nama_lengkap,
                        'tanggal_mulai' => $record->tanggal_mulai ?? now()->toDateString(),
                    ]
                );
            }
        } else {
            AnggotaTim::where('tim_id', $record->id)
                ->where('jabatan_tim', AnggotaTim::PILOT)
                ->delete();
        }
    }
}
