<?php

namespace App\Http\Controllers;

use App\Models\Distrik;
use App\Models\Drone;
use App\Models\EksporLaporan;
use App\Models\LaporanRealisasi;
use App\Models\Tim;
use App\Services\EksporExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pengelolaan Ekspor Laporan Excel Realisasi (FR-24, FR-25).
 * Khusus Admin dan Atasan.
 */
class EksporLaporanController extends Controller
{
    public function __construct(private readonly EksporExcelService $eksporService) {}

    public function index(Request $request): View
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $distrikId = $request->input('distrik_id');
        $timId = $request->input('tim_id');
        $droneId = $request->input('drone_id');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $tampilkanHarga = $request->boolean('tampilkan_harga', true);

        // Riwayat ekspor sebelumnya
        $riwayatEkspor = EksporLaporan::with('pengekspor')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // Jumlah laporan disetujui yang siap diekspor pada filter ini
        $jumlahSiapEkspor = LaporanRealisasi::query()
            ->where('status_laporan', LaporanRealisasi::DISETUJUI)
            ->when($tanggalMulai && $tanggalSelesai, function ($q) use ($tanggalMulai, $tanggalSelesai) {
                $q->whereHas('detail', fn ($dq) => $dq->whereBetween('tanggal_pekerjaan', [$tanggalMulai, $tanggalSelesai]));
            }, function ($q) use ($bulan, $tahun) {
                $q->where('periode_bulan', $bulan)
                    ->where('periode_tahun', $tahun);
            })
            ->when($distrikId, fn ($q) => $q->where('distrik_id', $distrikId))
            ->when($timId, fn ($q) => $q->where('tim_id', $timId))
            ->when($droneId, fn ($q) => $q->whereHas('tim', fn ($timQ) => $timQ->where('drone_id', $droneId)))
            ->count();

        $daftarDistrik = Distrik::where('aktif', true)->orderBy('nama_distrik')->get();
        $daftarTim = Tim::where('status_tim', 'aktif')->orderBy('nama_tim')->get();
        $daftarDrone = Drone::orderBy('nomor_drone')->get();

        return view('ekspor.index', compact(
            'riwayatEkspor', 'jumlahSiapEkspor', 'daftarDistrik', 'daftarTim', 'daftarDrone',
            'bulan', 'tahun', 'distrikId', 'timId', 'droneId', 'tanggalMulai', 'tanggalSelesai', 'tampilkanHarga'
        ));
    }

    public function ekspor(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'distrik_id' => ['nullable', 'exists:distrik,id'],
            'tim_id' => ['nullable', 'exists:tim,id'],
            'drone_id' => ['nullable', 'exists:drone,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'tampilkan_harga' => ['nullable', 'boolean'],
            'format' => ['nullable', 'string', 'in:excel,pdf'],
        ]);

        $format = $request->input('format', 'excel');

        if ($format === 'pdf') {
            $hasil = $this->eksporService->buatPdfRekapitulasi(
                (int) $data['bulan'],
                (int) $data['tahun'],
                ! empty($data['distrik_id']) ? (int) $data['distrik_id'] : null,
                ! empty($data['tim_id']) ? (int) $data['tim_id'] : null,
                $data['tanggal_mulai'] ?? null,
                $data['tanggal_selesai'] ?? null,
                $request->boolean('tampilkan_harga', true),
                ! empty($data['drone_id']) ? (int) $data['drone_id'] : null
            );

            return response()->download($hasil['path'], $hasil['nama'], [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $hasil = $this->eksporService->buatRekapitulasi(
            (int) $data['bulan'],
            (int) $data['tahun'],
            ! empty($data['distrik_id']) ? (int) $data['distrik_id'] : null,
            ! empty($data['tim_id']) ? (int) $data['tim_id'] : null,
            $data['tanggal_mulai'] ?? null,
            $data['tanggal_selesai'] ?? null,
            $request->boolean('tampilkan_harga', true),
            ! empty($data['drone_id']) ? (int) $data['drone_id'] : null
        );

        return response()->download($hasil['path'], $hasil['nama'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function unduh(EksporLaporan $ekspor): BinaryFileResponse
    {
        $pathAbsolut = storage_path('app/public/' . $ekspor->lokasi_berkas);

        if (! file_exists($pathAbsolut)) {
            abort(404, 'Berkas ekspor tidak ditemukan di penyimpanan server.');
        }

        return response()->download($pathAbsolut, $ekspor->nama_berkas);
    }

    public function unduhPdf(EksporLaporan $ekspor): BinaryFileResponse
    {
        $lokasiPdf = preg_replace('/\.xlsx$/i', '.pdf', $ekspor->lokasi_berkas);
        $pathPdf = storage_path('app/public/' . $lokasiPdf);

        if (! file_exists($pathPdf)) {
            $hasil = $this->eksporService->buatPdfRekapitulasi(
                (int) $ekspor->periode_bulan,
                (int) $ekspor->periode_tahun,
                null,
                null,
                $ekspor->tanggal_mulai,
                $ekspor->tanggal_selesai,
                (bool) ($ekspor->opsi_harga ?? true),
                null
            );
            $pathPdf = $hasil['path'];
        }

        $namaPdf = preg_replace('/\.xlsx$/i', '.pdf', $ekspor->nama_berkas);

        return response()->download($pathPdf, $namaPdf, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
