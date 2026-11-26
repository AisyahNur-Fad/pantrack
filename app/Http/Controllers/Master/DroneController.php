<?php

namespace App\Http\Controllers\Master;

use App\Models\Distrik;
use App\Models\Drone;
use App\Models\Pt;
use App\Models\Tim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DroneController extends MasterDataController
{
    private const WARNA_STATUS = [
        Drone::STATUS_AKTIF => 'success',
        Drone::STATUS_PERAWATAN => 'warning',
        Drone::STATUS_TIDAK_BEROPERASI => 'danger',
    ];

    protected function modelClass(): string
    {
        return Drone::class;
    }

    protected function judul(): string
    {
        return 'Drone';
    }

    protected function prefixRute(): string
    {
        return 'master.drone';
    }

    protected function ikon(): string
    {
        return 'bi-cpu';
    }

    protected function deskripsi(): string
    {
        return 'Armada drone agrikultur, status operasional, dan monitoring penugasan lapangan.';
    }

    protected function relasi(): array
    {
        return ['timAktif.distrik.pt', 'timAktif.kepalaTim'];
    }

    protected function pencarian(): array
    {
        return ['nomor_drone', 'nama_drone', 'tipe_drone', 'catatan_perawatan', 'keterangan'];
    }

    protected function urutan(): array
    {
        return ['nomor_drone' => 'asc'];
    }

    protected function filter(): array
    {
        return [
            ['name' => 'status_operasional', 'label' => 'Semua status', 'options' => Drone::statusOperasional()],
        ];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Nomor drone', 'key' => 'nomor_drone'],
            ['label' => 'Nama drone', 'key' => 'nama_drone'],
            ['label' => 'Tipe drone', 'key' => 'tipe_drone'],
            ['label' => 'Status operasional', 'key' => 'status_operasional', 'tipe' => 'status_drone', 'opsi' => Drone::statusOperasional(), 'badge' => self::WARNA_STATUS],
            ['label' => 'Penugasan (On Duty)', 'key' => 'lokasi_penugasan', 'tipe' => 'lokasi_drone'],
            ['label' => 'Catatan Perawatan / Kondisi', 'key' => 'catatan_perawatan', 'tipe' => 'catatan_perawatan'],
            ['label' => 'Keterangan', 'key' => 'keterangan'],
        ];
    }

    protected function field(): array
    {
        return [
            ['name' => 'nomor_drone', 'label' => 'Nomor drone', 'tipe' => 'teks', 'wajib' => true, 'bantuan' => 'Nomor unik unit drone (contoh: 601, 706, 903)'],
            ['name' => 'nama_drone', 'label' => 'Nama drone', 'tipe' => 'teks', 'wajib' => true, 'bantuan' => 'Nama vendor / pemilik unit drone (contoh: PAB DL, ESJB, CSP)'],
            ['name' => 'tipe_drone', 'label' => 'Tipe drone', 'tipe' => 'teks', 'wajib' => true, 'bantuan' => 'Model unit armada (contoh: T70P, T50, T25P, T25, T20P, T55)'],
            ['name' => 'status_operasional', 'label' => 'Status operasional', 'tipe' => 'pilih', 'options' => Drone::statusOperasional(), 'wajib' => true, 'default' => Drone::STATUS_AKTIF],
            ['name' => 'catatan_perawatan', 'label' => 'Catatan Perawatan / Kendala Unit', 'tipe' => 'area', 'lebar' => 'penuh', 'bantuan' => 'Tuliskan rincian servis atau kendala unit jika status Perawatan atau Tidak Beroperasi (contoh: Ganti 2 propeller & kalibrasi kompas).'],
            ['name' => 'ambang_batas_servis_ha', 'label' => 'Batas Jam Terbang Servis (Ha)', 'tipe' => 'angka', 'step' => '0.01', 'wajib' => false, 'default' => 200.00],
            ['name' => 'keterangan', 'label' => 'Keterangan Tambahan', 'tipe' => 'area', 'lebar' => 'penuh', 'bantuan' => 'Catatan bebas operasional unit jika ada (opsional).'],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'nomor_drone' => ['required', 'string', 'max:50', Rule::unique('drone', 'nomor_drone')->ignore($record?->id)],
            'nama_drone' => ['required', 'string', 'max:120'],
            'tipe_drone' => ['required', 'string', 'max:100'],
            'status_operasional' => ['required', Rule::in(array_keys(Drone::statusOperasional()))],
            'catatan_perawatan' => ['nullable', 'string', 'max:1000'],
            'ambang_batas_servis_ha' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function index(Request $request): View
    {
        $response = parent::index($request);
        $data = $response->getData();

        // Ringkasan status armada
        $totalArmada = Drone::count();
        $totalAktif = Drone::where('status_operasional', Drone::STATUS_AKTIF)->count();
        $totalPerawatan = Drone::where('status_operasional', Drone::STATUS_PERAWATAN)->count();
        $totalTidakBeroperasi = Drone::where('status_operasional', Drone::STATUS_TIDAK_BEROPERASI)->count();
        $totalOnDuty = Drone::whereHas('timAktif')->count();
        $totalStandby = max(0, $totalAktif - $totalOnDuty);

        // Sebaran armada per PT & Distrik
        $sebaranPt = Pt::with(['distrik' => function ($q) {
            $q->withCount(['tim as drone_aktif_count' => function ($tq) {
                $tq->where('status_tim', Tim::STATUS_AKTIF);
            }]);
        }])->where('aktif', true)->orderBy('nama_pt')->get();

        $data['ringkasanArmada'] = [
            'total' => $totalArmada,
            'aktif' => $totalAktif,
            'perawatan' => $totalPerawatan,
            'tidak_beroperasi' => $totalTidakBeroperasi,
            'on_duty' => $totalOnDuty,
            'standby' => $totalStandby,
        ];
        $data['sebaranPt'] = $sebaranPt;

        return view('master.index', $data);
    }

    protected function setelahSimpan(Model $record, array $data, bool $baru): void
    {
        // Pastikan drone yang berstatus aktif tidak menyimpan catatan kendala lama
        if ($record instanceof Drone && $record->status_operasional === Drone::STATUS_AKTIF && $record->catatan_perawatan !== null) {
            $record->updateQuietly(['catatan_perawatan' => null]);
        }
    }

    /**
     * Endpoint untuk memperbarui status operasional dan catatan perawatan dengan cepat (1-klik modal).
     */
    public function updateStatus(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $drone = Drone::findOrFail($id);

        $validated = $request->validate([
            'status_operasional' => ['required', Rule::in(array_keys(Drone::statusOperasional()))],
            'catatan_perawatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $statusLama = $drone->status_operasional;
        $statusBaru = $validated['status_operasional'];
        $catatanInput = trim($validated['catatan_perawatan'] ?? '');

        // Logika Pintar Sinkronisasi:
        // 1. Jika pengguna mengetik catatan kendala/perawatan tetapi statusnya masih 'aktif',
        //    sistem secara otomatis mengalihkan status unit ke 'perawatan'.
        if ($statusBaru === Drone::STATUS_AKTIF && !empty($catatanInput)) {
            $statusBaru = Drone::STATUS_PERAWATAN;
            $catatan = $catatanInput;
        } elseif ($statusBaru === Drone::STATUS_AKTIF) {
            // 2. Jika status diset 'aktif' (unit sehat), otomatis bersihkan catatan kendala lama.
            $catatan = null;
        } else {
            // 3. Jika status 'perawatan' atau 'tidak_beroperasi', simpan catatan jika ada.
            $catatan = !empty($catatanInput) ? $catatanInput : null;
        }

        $drone->update([
            'status_operasional' => $statusBaru,
            'catatan_perawatan' => $catatan,
        ]);

        $labelBaru = Drone::statusOperasional()[$statusBaru];

        $keteranganAktivitas = $statusBaru === Drone::STATUS_AKTIF
            ? "Status drone {$drone->nomor_drone} dipulihkan ke Aktif (siap kerja). Catatan kendala sebelumnya telah dibersihkan."
            : "Status drone {$drone->nomor_drone} diubah dari {$statusLama} menjadi {$labelBaru}." . ($drone->catatan_perawatan ? " Catatan: {$drone->catatan_perawatan}" : "");

        $this->aktivitas->catat(
            'Perbarui status drone',
            $drone,
            $keteranganAktivitas
        );

        $pesanFlash = $statusBaru === Drone::STATUS_AKTIF
            ? "Status unit drone {$drone->nomor_drone} berhasil diaktifkan kembali (siap kerja)."
            : "Status unit drone {$drone->nomor_drone} berhasil diperbarui menjadi {$labelBaru}.";

        if ($request->wantsJson()) {
            return response()->json([
                'sukses' => true,
                'status_operasional' => $drone->status_operasional,
                'label' => $labelBaru,
                'catatan_perawatan' => $drone->catatan_perawatan ?? '',
                'pesan' => $pesanFlash,
            ]);
        }

        return back()->with('sukses', $pesanFlash);
    }

    /**
     * Toggle status yang kompatibel mundur untuk test suite.
     */
    public function toggleStatus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        if ($request->has('status_operasional')) {
            return $this->updateStatus($request, $id);
        }

        return parent::toggleStatus($request, $id);
    }
}
