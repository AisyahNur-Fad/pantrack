@extends('layouts.app')

@section('judul', 'Ubah Laporan Realisasi: ' . ($realisasi->spk?->nomor_spk ?? $realisasi->nomor_laporan))

@section('konten')
    <x-halaman-kepala
        :judul="'Ubah Laporan Realisasi: ' . ($realisasi->spk?->nomor_spk ?? $realisasi->nomor_laporan)"
        deskripsi="Sesuaikan rincian pengerjaan petak atau perbaiki data sesuai arahan kepala lapangan."
        ikon="bi-pencil-square">
        <a href="{{ route('realisasi.show', $realisasi) }}" class="tombol tombol--garis">
            <i class="bi bi-arrow-left"></i> Kembali ke Detail
        </a>
    </x-halaman-kepala>

    <style>
        /* Desain Kartu Screening Pilihan Interaktif & Ramah Lapangan (Touch-Friendly) */
        .screening-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            margin-top: 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .screening-header {
            margin-bottom: 14px;
        }
        .screening-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .screening-desc {
            font-size: 12.5px;
            color: #64748b;
            margin: 0;
            line-height: 1.45;
        }
        .grid-kartu-opsi {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
        }
        .kartu-opsi {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .kartu-opsi:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }
        .kartu-opsi input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
        .kartu-opsi__indikator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .kartu-opsi__indikator-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: transparent;
            transition: all 0.2s ease;
        }
        .kartu-opsi__ikon {
            font-size: 24px;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .kartu-opsi__teks {
            flex: 1;
        }
        .kartu-opsi__judul {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 2px;
        }
        .kartu-opsi__ket {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Lancar */
        .kartu-opsi--lancar.terpilih,
        .kartu-opsi--lancar:has(input:checked) {
            border-color: #10b981;
            background: #f0fdf4;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.12);
        }
        .kartu-opsi--lancar.terpilih .kartu-opsi__indikator,
        .kartu-opsi--lancar:has(input:checked) .kartu-opsi__indikator {
            border-color: #10b981;
        }
        .kartu-opsi--lancar.terpilih .kartu-opsi__indikator-dot,
        .kartu-opsi--lancar:has(input:checked) .kartu-opsi__indikator-dot {
            background: #10b981;
        }
        .kartu-opsi--lancar.terpilih .kartu-opsi__judul,
        .kartu-opsi--lancar:has(input:checked) .kartu-opsi__judul {
            color: #065f46;
        }

        /* Kendala */
        .kartu-opsi--kendala.terpilih,
        .kartu-opsi--kendala:has(input:checked) {
            border-color: #ef4444;
            background: #fef2f2;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.12);
        }
        .kartu-opsi--kendala.terpilih .kartu-opsi__indikator,
        .kartu-opsi--kendala:has(input:checked) .kartu-opsi__indikator {
            border-color: #ef4444;
        }
        .kartu-opsi--kendala.terpilih .kartu-opsi__indikator-dot,
        .kartu-opsi--kendala:has(input:checked) .kartu-opsi__indikator-dot {
            background: #ef4444;
        }
        .kartu-opsi--kendala.terpilih .kartu-opsi__judul,
        .kartu-opsi--kendala:has(input:checked) .kartu-opsi__judul {
            color: #991b1b;
        }

        /* BBM Ada */
        .kartu-opsi--bbm-ada.terpilih,
        .kartu-opsi--bbm-ada:has(input:checked) {
            border-color: #0284c7;
            background: #f0f9ff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.12);
        }
        .kartu-opsi--bbm-ada.terpilih .kartu-opsi__indikator,
        .kartu-opsi--bbm-ada:has(input:checked) .kartu-opsi__indikator {
            border-color: #0284c7;
        }
        .kartu-opsi--bbm-ada.terpilih .kartu-opsi__indikator-dot,
        .kartu-opsi--bbm-ada:has(input:checked) .kartu-opsi__indikator-dot {
            background: #0284c7;
        }
        .kartu-opsi--bbm-ada.terpilih .kartu-opsi__judul,
        .kartu-opsi--bbm-ada:has(input:checked) .kartu-opsi__judul {
            color: #0369a1;
        }

        /* BBM Tanpa */
        .kartu-opsi--bbm-tanpa.terpilih,
        .kartu-opsi--bbm-tanpa:has(input:checked) {
            border-color: #64748b;
            background: #f8fafc;
            box-shadow: 0 4px 12px rgba(100, 116, 139, 0.12);
        }
        .kartu-opsi--bbm-tanpa.terpilih .kartu-opsi__indikator,
        .kartu-opsi--bbm-tanpa:has(input:checked) .kartu-opsi__indikator {
            border-color: #64748b;
        }
        .kartu-opsi--bbm-tanpa.terpilih .kartu-opsi__indikator-dot,
        .kartu-opsi--bbm-tanpa:has(input:checked) .kartu-opsi__indikator-dot {
            background: #64748b;
        }
        .kartu-opsi--bbm-tanpa.terpilih .kartu-opsi__judul,
        .kartu-opsi--bbm-tanpa:has(input:checked) .kartu-opsi__judul {
            color: #1e293b;
        }

        /* Panel Turunan / Sub */
        .panel-turunan {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
            animation: muncul .25s ease both;
        }
        .pilihan-dampak-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }
        .kartu-sub-opsi {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        .kartu-sub-opsi:hover {
            border-color: #94a3b8;
        }
        .kartu-sub-opsi input[type="radio"] {
            accent-color: #dc2626;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .kartu-sub-opsi.terpilih,
        .kartu-sub-opsi:has(input:checked) {
            border-color: #dc2626;
            background: #fff5f5;
            color: #991b1b;
        }
    </style>

    <div class="kartu p-6 sm:p-8">
        <form method="POST" action="{{ route('realisasi.update', $realisasi) }}" enctype="multipart/form-data" class="alur-vertikal">
            @csrf
            @method('PUT')

            <input type="hidden" name="nomor_laporan" value="{{ $realisasi->getRawOriginal('nomor_laporan') }}">

            {{-- Banner Peringatan Status Drone (Full-width agar tidak merusak simetri kolom grid) --}}
            <div id="peringatanDrone" class="alert alert--bahaya d-none" style="font-size: 0.875rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span id="pesanPeringatanDrone">Perhatian: Unit drone pada tim ini sedang dalam masa perawatan/servis!</span>
            </div>

            {{-- Baris 1: Tim Drone & SPK (2 Kolom Lebar agar Nama Tim & No SPK tidak terpotong) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <x-field label="Tim Drone Operasional" for="tim_id" :wajib="true" bantuan="Pilih tim unit drone yang beroperasi. Drone dalam perawatan otomatis terkunci.">
                    <select id="tim_id" name="tim_id" class="masukan @error('tim_id') masukan--error @enderror" required>
                        @foreach ($daftarTim as $t)
                            @php
                                $pilotNama = $t->anggota->firstWhere('jabatan_tim', 'pilot')?->nama_anggota ?? $t->kepalaTim?->nama_lengkap;
                                $copilotNama = $t->anggota->firstWhere('jabatan_tim', 'co_pilot')?->nama_anggota;
                                $droneStatus = $t->drone?->status_operasional ?? 'aktif';
                                $droneTipe = $t->drone ? ($t->drone->nomor_drone . (!empty($t->drone->nama_drone) ? ' · ' . $t->drone->nama_drone : '') . ' (' . $t->drone->tipe_drone . ')') : '';
                                $dronePerluServis = $t->drone?->perlu_servis ? '1' : '0';
                                $droneTotalHa = number_format($t->drone?->total_luas_terbang_ha ?? 0, 1, ',', '.');
                                $droneBatasHa = number_format($t->drone?->ambang_batas_servis_ha ?? 200, 1, ',', '.');
                                $isPerawatan = in_array($droneStatus, [\App\Models\Drone::STATUS_PERAWATAN, \App\Models\Drone::STATUS_TIDAK_BEROPERASI]);
                            @endphp
                            <option value="{{ $t->id }}" @selected(old('tim_id', $realisasi->tim_id) == $t->id)
                                    @disabled($isPerawatan && (int)$realisasi->tim_id !== (int)$t->id)
                                    data-pilot="{{ $pilotNama }}"
                                    data-copilot="{{ $copilotNama }}"
                                    data-drone-status="{{ $droneStatus }}"
                                    data-drone-tipe="{{ $droneTipe }}"
                                    data-drone-catatan="{{ e($t->drone?->catatan_perawatan ?? '') }}"
                                    data-drone-perlu-servis="{{ $dronePerluServis }}"
                                    data-drone-total-ha="{{ $droneTotalHa }}"
                                    data-drone-batas-ha="{{ $droneBatasHa }}">
                                @if ($isPerawatan)
                                    ⛔ {{ $t->nama_tim }} (⚠️ Drone {{ $t->drone?->nomor_drone }} sedang {{ strtoupper($droneStatus) }}{{ $t->drone?->catatan_perawatan ? ': '.$t->drone->catatan_perawatan : '' }} - TIDAK DAPAT DIGUNAKAN)
                                @else
                                    {{ $t->nama_tim }} ({{ $t->distrik?->nama_distrik ?? 'Semua Distrik' }}) &bull; Unit: {{ $t->drone?->nomor_drone ?? '-' }}{{ !empty($t->drone?->nama_drone) ? ' ('.$t->drone->nama_drone.')' : '' }} &bull; Pilot: {{ $pilotNama ?? '-' }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </x-field>

                @php
                    $spkTerpilih = old('spk_id', $realisasi->spk_id);
                @endphp
                <x-field label="Surat Perintah Kerja (SPK)" for="spk_id" :wajib="true" bantuan="Pilih SPK yang telah diterbitkan oleh Kepala Lapangan.">
                    <select id="spk_id" name="spk_id" class="masukan @error('spk_id') masukan--error @enderror" required>
                        <option value="">-- Pilih SPK dari Kepala Lapangan --</option>
                        @foreach ($daftarSpk as $s)
                            <option value="{{ $s->id }}" @selected((string) $spkTerpilih === (string) $s->id)
                                    data-distrik="{{ $s->distrik_id }}"
                                    data-bulan="{{ $s->periode_bulan }}"
                                    data-tahun="{{ $s->periode_tahun }}"
                                    data-pekerjaan="{{ $s->jenis_pekerjaan_id }}">
                                {{ $s->nomor_spk }} &bull; Distrik {{ $s->distrik?->nama_distrik ?? '-' }} (Periode {{ Carbon\Carbon::create($s->periode_tahun, $s->periode_bulan, 1)->translatedFormat('M Y') }})
                            </option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            {{-- Baris 2: Distrik, Bulan, dan Tahun (3 Kolom Simetris & Pas Ukurannya) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <x-field label="Distrik Wilayah Kerja" for="distrik_id" :wajib="true" bantuan="Distrik wilayah penugasan SPK.">
                    <select id="distrik_id" name="distrik_id" class="masukan @error('distrik_id') masukan--error @enderror" required>
                        <option value="">Pilih Distrik</option>
                        @foreach ($daftarDistrik as $d)
                            <option value="{{ $d->id }}" @selected(old('distrik_id', $realisasi->distrik_id) == $d->id)>{{ $d->nama_distrik }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field label="Periode Bulan" for="periode_bulan" :wajib="true" bantuan="Bulan pelaksanaan operasional.">
                    <select id="periode_bulan" name="periode_bulan" class="masukan @error('periode_bulan') masukan--error @enderror" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(old('periode_bulan', $realisasi->periode_bulan) == $m)>
                                {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </x-field>

                <x-field label="Periode Tahun" for="periode_tahun" :wajib="true" bantuan="Tahun pelaksanaan operasional.">
                    <select id="periode_tahun" name="periode_tahun" class="masukan @error('periode_tahun') masukan--error @enderror" required>
                        @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" @selected(old('periode_tahun', $realisasi->periode_tahun) == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </x-field>
            </div>

            <div class="kartu mt-3 mb-2 p-3" style="background: var(--warna-latar-kartu); border: 1px dashed var(--warna-garis);">
                <div class="flex-antara item-tengah mb-2">
                    <h3 class="judul-sub" style="font-size: 0.95rem;">
                        <i class="bi bi-people teks-utama"></i> Personil & Anggota Tim Lapangan (FR-08)
                    </h3>
                    <span class="teks-pudar teks-kecil">Nama pekerja yang bertugas pada penerbangan ini</span>
                </div>
                <div class="grid-form">
                    <x-field label="Nama Pilot Drone" for="nama_pilot" :wajib="true" bantuan="Nama pilot drone yang bertugas.">
                        <input type="text" id="nama_pilot" name="nama_pilot"
                               value="{{ old('nama_pilot', $realisasi->nama_pilot) }}"
                               placeholder="Contoh: Budi Santoso"
                               class="masukan @error('nama_pilot') masukan--error @enderror" required>
                    </x-field>

                    <x-field label="Nama Co-Pilot" for="nama_copilot" bantuan="Nama pendamping penerbangan (opsional).">
                        <input type="text" id="nama_copilot" name="nama_copilot"
                               value="{{ old('nama_copilot', $realisasi->nama_copilot) }}"
                               placeholder="Contoh: Ahmad Fauzi"
                               class="masukan @error('nama_copilot') masukan--error @enderror">
                    </x-field>

                    <div class="form-grup">
                        <div class="flex-antara item-tengah mb-1">
                            <label class="label mb-0">
                                Nama Helper (Warga Lokal)
                            </label>
                            <span class="lencana lencana--biru teks-kecil" id="lencana-jumlah-helper" style="font-size: 11px;">Daftar Helper</span>
                        </div>

                        <div id="wadah-helper" style="display: flex; flex-direction: column; gap: 8px;">
                            @php
                                $inputHelper = old('nama_helper');
                                if ($inputHelper === null) {
                                    $inputHelper = $realisasi->daftar_helper;
                                } elseif (! is_array($inputHelper)) {
                                    $inputHelper = array_filter(array_map('trim', explode(',', (string) $inputHelper)));
                                }
                                if (empty($inputHelper)) {
                                    $inputHelper = [''];
                                }
                                $inputHelper = array_values($inputHelper);
                            @endphp

                            @foreach ($inputHelper as $idx => $val)
                                <div class="baris-helper flex item-tengah gap-2" data-index="{{ $idx + 1 }}">
                                    <div style="position: relative; flex: 1;">
                                        <span class="teks-pudar teks-kecil nomor-helper" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; font-size: 11px; pointer-events: none; color: #64748b;">
                                            #{{ $idx + 1 }}
                                        </span>
                                        <input type="text"
                                               name="nama_helper[]"
                                               class="masukan masukan-helper"
                                               style="padding-left: 38px !important;"
                                               placeholder="Contoh: Helper {{ $idx + 1 }} (Warga Lokal)"
                                               value="{{ $val }}"
                                               maxlength="150">
                                    </div>
                                    <button type="button"
                                            class="tombol tombol--garis tombol--kecil teks-bahaya btn-hapus-helper"
                                            title="Hapus helper ini"
                                            style="{{ count($inputHelper) <= 1 ? 'display: none;' : '' }}">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-2 flex item-tengah gap-2">
                            <button type="button" id="btn-tambah-helper" class="tombol tombol--garis tombol--kecil">
                                <i class="bi bi-plus-circle"></i> Tambah Helper
                            </button>
                        </div>
                        <small class="bantuan d-block mt-1">Helper lokal/warga harian (bisa menambahkan helper tanpa batas jumlah). Upah helper dihitung per hektar.</small>
                    </div>
                </div>
            </div>

            <x-field label="Tambah Lampiran Foto Bukti Lapangan" for="lampiran" bantuan="Format: JPG, PNG, PDF. Maksimal 5 MB per berkas.">
                <input type="file" id="lampiran" name="lampiran[]" class="masukan" multiple accept=".jpg,.jpeg,.png,.pdf">
            </x-field>

            {{-- 1. Screening Kondisi & Kendala Lapangan (Interactive Toggle Cards) --}}
            @php
                $adaBbmTerkait = $realisasi->transaksiBbm->isNotEmpty();
                $existingBbmItems = $realisasi->transaksiBbm->map(fn($t) => [
                    'jenis_bbm_id' => $t->jenis_bbm_id,
                    'keperluan' => $t->keperluan,
                    'jumlah_liter' => (float)$t->jumlah_liter,
                    'keterangan' => $t->keterangan,
                ])->toArray();
                $opsiAdaBbmVal = old('ada_bbm', $adaBbmTerkait || $realisasi->ada_bbm ? '1' : '0');
                $opsiAdaKendalaVal = old('ada_kendala', $realisasi->ada_kendala ? '1' : '0');
            @endphp

            <div class="screening-section">
                <div class="screening-header">
                    <label class="screening-title">
                        <i class="bi bi-shield-exclamation teks-peringatan"></i> Kondisi Operasional Hari Ini <span class="wajib">*</span>
                    </label>
                    <p class="screening-desc">
                        Pilih status operasional penerbangan hari ini. Jika memilih terdapat kendala, tentukan apakah pekerjaan terhenti total atau dapat selesai sebagian.
                    </p>
                </div>

                <div class="grid-kartu-opsi">
                    <label class="kartu-opsi kartu-opsi--lancar {{ $opsiAdaKendalaVal === '0' ? 'terpilih' : '' }}" id="labelKondisiLancar">
                        <input type="radio" name="ada_kendala" value="0" id="kondisiLancar" @checked($opsiAdaKendalaVal === '0') required>
                        <div class="kartu-opsi__indikator">
                            <div class="kartu-opsi__indikator-dot"></div>
                        </div>
                        <i class="bi bi-check-circle-fill kartu-opsi__ikon" style="color: #10b981;"></i>
                        <div class="kartu-opsi__teks">
                            <div class="kartu-opsi__judul">Operasional Lancar</div>
                            <div class="kartu-opsi__ket">Penerbangan berjalan normal tanpa hambatan cuaca atau teknis armada.</div>
                        </div>
                    </label>

                    <label class="kartu-opsi kartu-opsi--kendala {{ $opsiAdaKendalaVal === '1' ? 'terpilih' : '' }}" id="labelKondisiKendala">
                        <input type="radio" name="ada_kendala" value="1" id="kondisiKendala" @checked($opsiAdaKendalaVal === '1') required>
                        <div class="kartu-opsi__indikator">
                            <div class="kartu-opsi__indikator-dot"></div>
                        </div>
                        <i class="bi bi-exclamation-triangle-fill kartu-opsi__ikon" style="color: #ef4444;"></i>
                        <div class="kartu-opsi__teks">
                            <div class="kartu-opsi__judul">Terdapat Kendala Lapangan</div>
                            <div class="kartu-opsi__ket">Hujan, armada rusak, ketek mati mesin, atau kendala akses lahan/hutan.</div>
                        </div>
                    </label>
                </div>

                {{-- Panel Input Rincian Kendala (Wajib jika ada_kendala == 1) --}}
                <div id="panelRincianKendala" class="panel-turunan" style="display: {{ $opsiAdaKendalaVal === '1' ? 'block' : 'none' }};">
                    <div style="background: #fff5f5; border: 1px solid #fecaca; border-radius: 12px; padding: 16px;">
                        <div style="font-weight: 700; color: #991b1b; font-size: 13.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-diagram-3-fill"></i> Dampak Kendala Terhadap Hasil Terbang:
                        </div>
                        
                        <div class="pilihan-dampak-grid">
                            <label class="kartu-sub-opsi {{ old('dampak_kendala', 'sebagian') === 'sebagian' ? 'terpilih' : '' }}" id="labelDampakSebagian">
                                <input type="radio" name="dampak_kendala" value="sebagian" id="dampakSebagian" @checked(old('dampak_kendala', 'sebagian') === 'sebagian')>
                                <span>
                                    <strong style="display: block;">Pekerjaan Sebagian</strong>
                                    <span class="teks-pudar teks-kecil" style="font-weight: normal;">Sebagian luasan petak berhasil diselesaikan</span>
                                </span>
                            </label>

                            <label class="kartu-sub-opsi {{ old('dampak_kendala') === 'total' ? 'terpilih' : '' }}" id="labelDampakTotal">
                                <input type="radio" name="dampak_kendala" value="total" id="dampakTotal" @checked(old('dampak_kendala') === 'total')>
                                <span>
                                    <strong style="display: block;">Batal Terbang / Berhenti Total (0.00 Ha)</strong>
                                    <span class="teks-pudar teks-kecil" style="font-weight: normal;">Otomatis set Luas Realisasi di bawah menjadi 0.00 Ha & terkunci</span>
                                </span>
                            </label>
                        </div>

                        <div id="alertBatalTerbang" class="alert alert--bahaya mb-3" style="padding: 10px 14px; font-size: 13px; display: {{ old('dampak_kendala') === 'total' ? 'flex' : 'none' }}; align-items: center; gap: 8px;">
                            <i class="bi bi-lock-fill" style="font-size: 16px;"></i>
                            <span><strong>Perhatian:</strong> Karena operasional batal terbang / berhenti total, kolom luas realisasi di tabel petak bawah <strong>diatur ke 0.00 Ha dan dikunci otomatis</strong>.</span>
                        </div>

                        <div class="form-grup mb-0">
                            <label class="form-label" for="catatan_kendala" style="color: #991b1b; font-weight: 700;">
                                Rincian Kendala Lapangan <span class="wajib">*</span>
                            </label>
                            <textarea id="catatan_kendala" name="catatan_kendala" rows="2" class="masukan @error('catatan_kendala') masukan--error @enderror"
                                      style="background: #ffffff; border-color: #fca5a5;"
                                      placeholder="Jelaskan secara spesifik faktor penghambat pekerjaan hari ini (misal: Hujan lebat pukul 11:00; atau ketek rusak saat menyeberang menuju petak)...">{{ old('catatan_kendala', $realisasi->catatan_kendala) }}</textarea>
                            <small class="form-bantuan" style="color: #b91c1c;">Wajib mengisi alasan kendala agar Kepala Lapangan dapat memvalidasi laporan.</small>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="garis-pemisah">

            {{-- 2. Rincian Realisasi Pekerjaan Petak (Target & Capaian Hektar Hari Ini) --}}
            <div>
                <div class="flex-antara item-tengah mb-3">
                    <div>
                        <h3 class="judul-sub">Rincian Realisasi Pekerjaan Petak (1 SPK = 1 Petak)</h3>
                        <p class="teks-pudar teks-kecil">Data petak, distrik, dan luas target terkunci otomatis sesuai penugasan SPK.</p>
                    </div>
                </div>

                <div class="tabel-bungkus">
                    <table class="tabel" id="tabelRealisasi">
                        <thead>
                            <tr>
                                <th style="width: 14%">Tanggal *</th>
                                <th style="width: 28%">Petak (Otomatis SPK) *</th>
                                <th style="width: 22%">Jenis Pekerjaan *</th>
                                <th style="width: 13%" class="angka">Luas SPK (Ha)</th>
                                <th style="width: 13%" class="angka">Luas Realisasi (Ha) *</th>
                                <th style="width: 10%; text-align: center;">🗺️ Mapping</th>
                            </tr>
                        </thead>
                        <tbody id="badanTabelRealisasi">
                            @php
                                $detailLama = $realisasi->detail->map(function ($d) use ($realisasi, $daftarPetak) {
                                    $luasSpk = 0;
                                    if ($realisasi->spk_id && $d->petak_id) {
                                        $dspk = \App\Models\DetailSpk::where('spk_id', $realisasi->spk_id)->where('petak_id', $d->petak_id)->first();
                                        $luasSpk = $dspk?->luas_target_ha ?? 0;
                                    }
                                    return [
                                        'tanggal_pekerjaan' => $d->tanggal_pekerjaan?->toDateString(),
                                        'petak_id' => $d->petak_id,
                                        'kode_petak_manual' => $d->kode_petak_manual,
                                        'jenis_pekerjaan_id' => $d->jenis_pekerjaan_id,
                                        'luas_spk_ha' => $luasSpk > 0 ? $luasSpk : '',
                                        'luas_realisasi_ha' => $d->luas_realisasi_ha,
                                        'perlu_mapping' => (bool) $d->perlu_mapping,
                                        'status_pekerjaan' => $d->status_pekerjaan,
                                        'keterangan_kendala' => $d->keterangan_kendala,
                                    ];
                                })->toArray();
                                $oldRows = old('petak', !empty($detailLama) ? $detailLama : []);
                            @endphp
                            @foreach ($oldRows as $i => $row)
                                @php
                                    $petakIdRow = $row['petak_id'] ?? '';
                                    $petakModel = $petakIdRow ? $daftarPetak->firstWhere('id', $petakIdRow) : null;
                                    $namaPetakTampil = '';
                                    if ($petakModel) {
                                        $namaPetakTampil = $petakModel->kode_petak . ($petakModel->nama_petak ? ' — ' . $petakModel->nama_petak : '');
                                        if ($petakModel->distrik) {
                                            $namaPetakTampil .= ' (' . $petakModel->distrik->nama_distrik . ')';
                                        }
                                    } elseif (!empty($row['kode_petak_manual'])) {
                                        $namaPetakTampil = $row['kode_petak_manual'];
                                    } elseif ($realisasi->spk && $realisasi->spk->detail->first()) {
                                        $firstSpkDet = $realisasi->spk->detail->first();
                                        if ($firstSpkDet->petak) {
                                            $namaPetakTampil = $firstSpkDet->petak->kode_petak . ($firstSpkDet->petak->nama_petak ? ' — ' . $firstSpkDet->petak->nama_petak : '');
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <input type="date" name="petak[{{ $i }}][tanggal_pekerjaan]"
                                               value="{{ $row['tanggal_pekerjaan'] ?? now()->toDateString() }}" class="masukan" required>
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="masukan text-petak-tampil bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200"
                                               style="background-color: #f1f5f9; color: #1e293b; cursor: not-allowed; font-weight: 600;"
                                               value="{{ $namaPetakTampil }}"
                                               placeholder="Otomatis dari SPK"
                                               readonly>
                                        <input type="hidden" name="petak[{{ $i }}][petak_id]" class="input-petak-id" value="{{ $row['petak_id'] ?? '' }}">
                                        <input type="hidden" name="petak[{{ $i }}][kode_petak_manual]" class="input-petak-manual" value="{{ $row['kode_petak_manual'] ?? '' }}">
                                        <input type="hidden" name="petak[{{ $i }}][status_pekerjaan]" class="input-status-pekerjaan" value="{{ $row['status_pekerjaan'] ?? 'selesai' }}">
                                        <input type="hidden" name="petak[{{ $i }}][keterangan_kendala]" class="input-ket-kendala" value="{{ $row['keterangan_kendala'] ?? '' }}">
                                    </td>
                                    <td>
                                        <select name="petak[{{ $i }}][jenis_pekerjaan_id]" class="masukan" required>
                                            @foreach ($daftarPekerjaan as $jp)
                                                <option value="{{ $jp->id }}" @selected(($row['jenis_pekerjaan_id'] ?? '') == $jp->id)>{{ $jp->nama_pekerjaan }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="petak[{{ $i }}][luas_spk_ha]"
                                               value="{{ $row['luas_spk_ha'] ?? '' }}"
                                               class="masukan angka bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                               style="background-color: #f1f5f9; color: #64748b; cursor: not-allowed;"
                                               placeholder="0.00" readonly>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="petak[{{ $i }}][luas_realisasi_ha]"
                                               value="{{ $row['luas_realisasi_ha'] ?? '' }}" class="masukan angka inputLuas" placeholder="0.00" required>
                                    </td>
                                    <td class="text-center" style="text-align: center;">
                                        <label class="flex item-tengah gap-1 cursor-pointer" style="font-size: 0.85rem; justify-content: center; display: inline-flex;" title="Centang jika petak ini mendapatkan pengerjaan mapping (+Rp 30.000/Ha)">
                                            <input type="checkbox" name="petak[{{ $i }}][perlu_mapping]" value="1"
                                                   @checked(!empty($row['perlu_mapping'])) class="checkPerluMapping">
                                            <span class="labelMapping teks-kecil teks-tebal" style="color: {{ !empty($row['perlu_mapping']) ? '#0284c7' : '#64748b' }};">
                                                {{ !empty($row['perlu_mapping']) ? '🗺️ Ya' : 'Tidak' }}
                                            </span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="baris-total" style="background: #f8fafc;">
                                <td colspan="6" style="padding: 12px 16px;">
                                    <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 20px;">
                                        <div style="font-size: 0.85rem; color: #475569;">
                                            <span style="font-weight: 600;">📅 TO DAY (Hari Terakhir):</span> 
                                            <span id="toDayLuasTampil" style="font-weight: 700; color: #0f172a; font-size: 0.95rem; margin-left: 4px;">0.00 Ha</span>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #475569;">
                                            <span style="font-weight: 600;">📈 MTD (Bulan Berjalan):</span> 
                                            <span id="totalLuasTampil" style="font-weight: 700; color: #059669; font-size: 1.05rem; margin-left: 4px;">0.00 Ha</span>
                                        </div>
                                        <div id="totalMappingTampil" style="font-size: 0.85rem; color: #0284c7; font-weight: 700;">
                                            0 Petak Map
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- 3. Keputusan & Pencatatan Konsumsi BBM Operasional (Diisi Terakhir Sebagai Penutup Hari) --}}
            <div class="screening-section">
                <div class="screening-header">
                    <label class="screening-title">
                        <i class="bi bi-fuel-pump-fill teks-utama"></i> Konsumsi BBM Operasional Hari Ini <span class="wajib">*</span>
                    </label>
                    <p class="screening-desc">
                        Wajib memilih apakah ada konsumsi BBM (bensin/solar) untuk drone, genset charger baterai, atau kendaraan tim hari ini.
                    </p>
                </div>

                <div class="grid-kartu-opsi">
                    <label class="kartu-opsi kartu-opsi--bbm-ada {{ $opsiAdaBbmVal === '1' ? 'terpilih' : '' }}" id="labelOpsiAdaBbm">
                        <input type="radio" name="ada_bbm" value="1" id="opsiAdaBbm" @checked($opsiAdaBbmVal === '1') required>
                        <div class="kartu-opsi__indikator">
                            <div class="kartu-opsi__indikator-dot"></div>
                        </div>
                        <i class="bi bi-droplet-fill kartu-opsi__ikon" style="color: #0284c7;"></i>
                        <div class="kartu-opsi__teks">
                            <div class="kartu-opsi__judul">Ada Penggunaan BBM</div>
                            <div class="kartu-opsi__ket">Catat liter bensin drone / genset charger / kendaraan operasional tim.</div>
                        </div>
                    </label>

                    <label class="kartu-opsi kartu-opsi--bbm-tanpa {{ $opsiAdaBbmVal === '0' ? 'terpilih' : '' }}" id="labelOpsiTanpaBbm">
                        <input type="radio" name="ada_bbm" value="0" id="opsiTanpaBbm" @checked($opsiAdaBbmVal === '0') required>
                        <div class="kartu-opsi__indikator">
                            <div class="kartu-opsi__indikator-dot"></div>
                        </div>
                        <i class="bi bi-slash-circle kartu-opsi__ikon" style="color: #64748b;"></i>
                        <div class="kartu-opsi__teks">
                            <div class="kartu-opsi__judul">Tidak Ada Penggunaan BBM</div>
                            <div class="kartu-opsi__ket">Gunakan opsi ini jika baterai di-charge via PLN atau BBM dari pihak kebun.</div>
                        </div>
                    </label>
                </div>

                {{-- Panel Multi-Input Repeater BBM (Jika ada_bbm == 1) --}}
                <div id="formBbmTerhubung" class="panel-turunan" style="display: {{ $opsiAdaBbmVal === '1' ? 'block' : 'none' }};">
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 16px;">
                        <div class="flex-antara item-tengah mb-2">
                            <strong style="color: #0369a1; font-size: 13.5px;">
                                <i class="bi bi-list-check"></i> Rincian Pemakaian BBM Lapangan
                            </strong>
                            <span class="teks-pudar teks-kecil">Stok BBM tim akan terpotong secara otomatis</span>
                        </div>
                        <div class="tabel-bungkus" style="margin-bottom: 0.75rem; background: #fff; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <table class="tabel" id="tabelBbmRepeater" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Jenis BBM <span class="wajib">*</span></th>
                                        <th style="width: 25%;">Kategori / Keperluan <span class="wajib">*</span></th>
                                        <th style="width: 20%;" class="angka">Jumlah (Liter) <span class="wajib">*</span></th>
                                        <th style="width: 25%;">Catatan / Detail</th>
                                        <th style="width: 5%;" class="aksi">Hapus</th>
                                    </tr>
                                </thead>
                                <tbody id="wadahBarisBbm">
                                    {{-- Baris dinamis diisi via JavaScript --}}
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="tombol tombol--garis tombol--kecil" id="btnTambahBarisBbm" style="background: #ffffff;">
                            <i class="bi bi-plus-circle"></i> + Tambah Rincian Penggunaan BBM
                        </button>
                    </div>
                </div>

                {{-- Panel Alasan Tanpa BBM (Jika ada_bbm == 0) --}}
                <div id="panelTanpaBbm" class="panel-turunan" style="display: {{ $opsiAdaBbmVal === '0' ? 'block' : 'none' }};">
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px;">
                        <label class="form-label mb-1" style="font-size: 13.5px; font-weight: 700; color: #1e293b;">
                            <i class="bi bi-lightning-charge teks-utama"></i> Alasan Tidak Menggunakan BBM <span class="wajib">*</span>
                        </label>
                        <p class="teks-pudar teks-kecil mb-2">Pilih alasan cepat dari daftar berikut atau ketik alasan sendiri:</p>
                        
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <select id="pilihanCepatBbm" class="masukan" style="font-weight: 500; background: #ffffff;">
                                <option value="">-- Pilih Alasan Cepat (Rekomendasi) --</option>
                                <option value="Baterai drone di-charge penuh di mess semalam (Listrik PLN)">⚡ Baterai drone di-charge penuh di mess semalam (Listrik PLN)</option>
                                <option value="BBM disediakan langsung oleh pihak kebun / klien">🏢 BBM disediakan langsung oleh pihak kebun / klien</option>
                                <option value="Operasional batal terbang / libur operasional">🚫 Operasional batal terbang / tidak ada operasional</option>
                                <option value="manual">✍️ Alasan Lainnya (Ketik Manual)...</option>
                            </select>

                            <div id="wadahInputManualBbm" style="display: none;">
                                <input type="text" id="keterangan_tanpa_bbm" name="keterangan_tanpa_bbm"
                                       value="{{ old('keterangan_tanpa_bbm', $realisasi->keterangan_tanpa_bbm) }}"
                                       placeholder="Ketik alasan spesifik tidak menggunakan BBM hari ini..."
                                       class="masukan @error('keterangan_tanpa_bbm') masukan--error @enderror"
                                       style="background: #ffffff;">
                            </div>
                        </div>
                        <small class="form-bantuan">Keterangan ini diperlukan untuk akurasi data inventaris bahan bakar.</small>
                    </div>
                </div>
            </div>

            <div class="bilah-aksi">
                <button type="submit" name="ajukan" value="1" class="tombol tombol--utama">
                    <i class="bi bi-send-check"></i> Simpan & Ajukan ke Kepala Lapangan
                </button>
                <button type="submit" name="ajukan" value="0" class="tombol tombol--garis">
                    <i class="bi bi-check2-circle"></i> Simpan Perubahan Draf
                </button>
                <a href="{{ route('realisasi.show', $realisasi) }}" class="tombol tombol--garis">Batal</a>
            </div>
        </form>
    </div>

    {{-- Template baris dinamis --}}
    <template id="tmplRowRealisasi">
        <tr>
            <td>
                <input type="date" name="petak[INDEX][tanggal_pekerjaan]" value="{{ now()->toDateString() }}" class="masukan" required>
            </td>
            <td>
                <input type="text"
                       class="masukan text-petak-tampil bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200"
                       style="background-color: #f1f5f9; color: #1e293b; cursor: not-allowed; font-weight: 600;"
                       placeholder="Otomatis dari SPK"
                       readonly>
                <input type="hidden" name="petak[INDEX][petak_id]" class="input-petak-id" value="">
                <input type="hidden" name="petak[INDEX][kode_petak_manual]" class="input-petak-manual" value="">
                <input type="hidden" name="petak[INDEX][status_pekerjaan]" class="input-status-pekerjaan" value="selesai">
                <input type="hidden" name="petak[INDEX][keterangan_kendala]" class="input-ket-kendala" value="">
            </td>
            <td>
                <select name="petak[INDEX][jenis_pekerjaan_id]" class="masukan" required>
                    @foreach ($daftarPekerjaan as $jp)
                        <option value="{{ $jp->id }}">{{ $jp->nama_pekerjaan }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="petak[INDEX][luas_spk_ha]"
                       class="masukan angka bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                       style="background-color: #f1f5f9; color: #64748b; cursor: not-allowed;"
                       placeholder="0.00" readonly>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="petak[INDEX][luas_realisasi_ha]" class="masukan angka inputLuas" placeholder="0.00" required>
            </td>
            <td class="text-center" style="text-align: center;">
                <label class="flex item-tengah gap-1 cursor-pointer" style="font-size: 0.85rem; justify-content: center; display: inline-flex;" title="Centang jika petak ini mendapatkan pengerjaan mapping (+Rp 30.000/Ha)">
                    <input type="checkbox" name="petak[INDEX][perlu_mapping]" value="1" class="checkPerluMapping">
                    <span class="labelMapping teks-kecil teks-tebal" style="color: #64748b;">Tidak</span>
                </label>
            </td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let index = {{ count($oldRows) }};
            const badanTabel = document.getElementById('badanTabelRealisasi');
            const tmpl = document.getElementById('tmplRowRealisasi').innerHTML;
            const totalTampil = document.getElementById('totalLuasTampil');

            const daftarSpkJson = @json($daftarSpk->keyBy('id'));
            const spkSelect = document.getElementById('spk_id');
            const distrikSelect = document.getElementById('distrik_id');
            const bulanSelect = document.getElementById('periode_bulan');
            const tahunSelect = document.getElementById('periode_tahun');

            function hitungTotal() {
                let totalMtd = 0;
                let countMapping = 0;
                let latestDate = null;
                let todayTotal = 0;
                const barisData = [];

                document.querySelectorAll('#badanTabelRealisasi tr').forEach(row => {
                    const tglInput = row.querySelector('input[type="date"]');
                    const luasInput = row.querySelector('.inputLuas');
                    const mapCheck = row.querySelector('.checkPerluMapping');

                    const luasVal = luasInput ? (parseFloat(luasInput.value) || 0) : 0;
                    totalMtd += luasVal;

                    if (mapCheck && mapCheck.checked) countMapping++;

                    if (tglInput && tglInput.value) {
                        const tgl = tglInput.value;
                        barisData.push({ tgl: tgl, luas: luasVal });
                        if (!latestDate || tgl > latestDate) {
                            latestDate = tgl;
                        }
                    }
                });

                if (latestDate) {
                    barisData.forEach(item => {
                        if (item.tgl === latestDate) {
                            todayTotal += item.luas;
                        }
                    });
                }

                totalTampil.textContent = totalMtd.toFixed(2) + ' Ha';

                const toDayEl = document.getElementById('toDayLuasTampil');
                if (toDayEl) {
                    toDayEl.textContent = todayTotal.toFixed(2) + ' Ha';
                }

                const mapTampil = document.getElementById('totalMappingTampil');
                if (mapTampil) {
                    mapTampil.textContent = countMapping > 0 ? (countMapping + ' Petak Map') : '0 Petak Map';
                }
            }

            function terapkanSpk(spkId) {
                if (!spkId || !daftarSpkJson[spkId]) return;
                const spk = daftarSpkJson[spkId];
                if (distrikSelect && spk.distrik_id) distrikSelect.value = spk.distrik_id;
                if (bulanSelect && spk.periode_bulan) bulanSelect.value = spk.periode_bulan;
                if (tahunSelect && spk.periode_tahun) tahunSelect.value = spk.periode_tahun;

                badanTabel.innerHTML = '';
                const html = tmpl.replace(/INDEX/g, 0);
                badanTabel.innerHTML = html;

                const row = badanTabel.firstElementChild;
                if (row) {
                    const textPetak = row.querySelector('.text-petak-tampil');
                    const inputPetakId = row.querySelector('.input-petak-id');
                    const inputPetakManual = row.querySelector('.input-petak-manual');
                    const jpSel = row.querySelector('select[name$="[jenis_pekerjaan_id]"]');
                    const spkHaInput = row.querySelector('input[name$="[luas_spk_ha]"]');
                    const mapCheck = row.querySelector('.checkPerluMapping');
                    const mapLabel = row.querySelector('.labelMapping');

                    const firstDetail = (spk.detail && spk.detail.length > 0) ? spk.detail[0] : null;
                    const targetPetakId = firstDetail ? firstDetail.petak_id : '';
                    const targetLuas = firstDetail ? (firstDetail.luas_target_ha || 0) : 0;
                    const perluMap = firstDetail ? !!firstDetail.perlu_mapping : false;

                    let namaPetakTampil = '';
                    if (firstDetail && firstDetail.petak) {
                        namaPetakTampil = firstDetail.petak.kode_petak + (firstDetail.petak.nama_petak ? ' — ' + firstDetail.petak.nama_petak : '');
                        if (spk.distrik && spk.distrik.nama_distrik) {
                            namaPetakTampil += ' (' + spk.distrik.nama_distrik + ')';
                        }
                    } else if (firstDetail && firstDetail.kode_petak_manual) {
                        namaPetakTampil = firstDetail.kode_petak_manual;
                    } else if (targetPetakId) {
                        namaPetakTampil = 'Petak #' + targetPetakId;
                    } else {
                        namaPetakTampil = 'Petak SPK ' + (spk.nomor_spk || '');
                    }

                    if (textPetak) textPetak.value = namaPetakTampil;
                    if (inputPetakId) inputPetakId.value = targetPetakId || '';
                    if (inputPetakManual) inputPetakManual.value = (firstDetail && firstDetail.kode_petak_manual) ? firstDetail.kode_petak_manual : '';

                    if (jpSel && spk.jenis_pekerjaan_id) jpSel.value = spk.jenis_pekerjaan_id;
                    if (spkHaInput) {
                        spkHaInput.value = targetLuas > 0 ? parseFloat(targetLuas).toFixed(2) : '0.00';
                        spkHaInput.readOnly = true;
                    }
                    if (mapCheck) {
                        mapCheck.checked = perluMap;
                        if (mapLabel) {
                            mapLabel.textContent = perluMap ? '🗺️ Ya' : 'Tidak';
                            mapLabel.style.color = perluMap ? '#0284c7' : '#64748b';
                        }
                    }
                }

                hitungTotal();
            }

            if (spkSelect) {
                spkSelect.addEventListener('change', () => {
                    const spkId = spkSelect.value;
                    terapkanSpk(spkId);
                });
            }

            badanTabel.addEventListener('input', (e) => {
                if (e.target.classList.contains('inputLuas')) {
                    hitungTotal();
                }
            });

            badanTabel.addEventListener('change', (e) => {
                if (e.target.type === 'date') {
                    hitungTotal();
                }
                if (e.target.classList.contains('checkPerluMapping')) {
                    const label = e.target.closest('label')?.querySelector('.labelMapping');
                    if (label) {
                        label.textContent = e.target.checked ? '🗺️ Ya' : 'Tidak';
                        label.style.color = e.target.checked ? '#0284c7' : '#64748b';
                    }
                    hitungTotal();
                }
            });

            // Otomatisasi personil pilot/co-pilot dan status drone dari tim
            const selTim = document.getElementById('tim_id');
            const inputPilot = document.getElementById('nama_pilot');
            const inputCopilot = document.getElementById('nama_copilot');
            const bannerDrone = document.getElementById('peringatanDrone');
            const pesanDrone = document.getElementById('pesanPeringatanDrone');

            function perbaruiPersonilTim(force) {
                if (!selTim) return;
                const opt = selTim.options[selTim.selectedIndex];
                if (!opt) return;
                const p = opt.getAttribute('data-pilot');
                const cp = opt.getAttribute('data-copilot');
                const ds = opt.getAttribute('data-drone-status');
                const dt = opt.getAttribute('data-drone-tipe');

                if (force) {
                    if (p) inputPilot.value = p;
                    if (cp) inputCopilot.value = cp;
                }

                const btnSubmit = document.querySelector('button[type="submit"]');
                const catatan = opt.getAttribute('data-drone-catatan');

                if (bannerDrone && pesanDrone) {
                    if (ds === 'perawatan' || ds === 'tidak_beroperasi') {
                        bannerDrone.classList.remove('d-none');
                        const statusLabel = ds === 'perawatan' ? 'PERAWATAN' : 'TIDAK BEROPERASI';
                        pesanDrone.textContent = `⛔ Unit drone ${dt} milik tim ini sedang berstatus '${statusLabel}'${catatan ? ' (' + catatan + ')' : ''}! Unit tidak dapat digunakan untuk penerbangan lapangan.`;
                        if (btnSubmit) { btnSubmit.disabled = true; btnSubmit.style.opacity = '0.5'; btnSubmit.title = 'Drone sedang dalam perawatan/tidak beroperasi'; }
                    } else if (opt.getAttribute('data-drone-perlu-servis') === '1') {
                        bannerDrone.classList.remove('d-none');
                        pesanDrone.textContent = `⚠️ Perhatian: Unit drone ${dt} telah mencapai ambang batas jam terbang servis (${opt.getAttribute('data-drone-total-ha')} / ${opt.getAttribute('data-drone-batas-ha')} Ha). Harap segera lakukan pemeliharaan berkala!`;
                        if (btnSubmit) { btnSubmit.disabled = false; btnSubmit.style.opacity = '1'; btnSubmit.title = ''; }
                    } else {
                        bannerDrone.classList.add('d-none');
                        if (btnSubmit) { btnSubmit.disabled = false; btnSubmit.style.opacity = '1'; btnSubmit.title = ''; }
                    }
                }
            }

            if (selTim) {
                selTim.addEventListener('change', () => perbaruiPersonilTim(true));
                perbaruiPersonilTim(false);
            }

            // Pengelolaan Input Dinamis Helper (Tanpa Batas Jumlah)
            const wadahHelper = document.getElementById('wadah-helper');
            const btnTambahHelper = document.getElementById('btn-tambah-helper');
            const lencanaJumlahHelper = document.getElementById('lencana-jumlah-helper');

            function perbaruiNomorHelper() {
                if (!wadahHelper) return;
                const barisList = wadahHelper.querySelectorAll('.baris-helper');
                const total = barisList.length;

                barisList.forEach((baris, i) => {
                    const no = i + 1;
                    baris.setAttribute('data-index', no);
                    const labelNo = baris.querySelector('.nomor-helper');
                    if (labelNo) labelNo.textContent = '#' + no;
                    const input = baris.querySelector('.masukan-helper');
                    if (input && !input.value) {
                        input.placeholder = `Contoh: Helper ${no} (Warga Lokal)`;
                    }
                    const btnHapus = baris.querySelector('.btn-hapus-helper');
                    if (btnHapus) {
                        btnHapus.style.display = total > 1 ? 'inline-flex' : 'none';
                    }
                });

                if (lencanaJumlahHelper) {
                    lencanaJumlahHelper.textContent = `${total} Orang`;
                }
            }

            if (btnTambahHelper && wadahHelper) {
                btnTambahHelper.addEventListener('click', () => {
                    const barisList = wadahHelper.querySelectorAll('.baris-helper');
                    const nextNo = barisList.length + 1;
                    const itemHtml = `
                        <div class="baris-helper flex item-tengah gap-2" data-index="${nextNo}">
                            <div style="position: relative; flex: 1;">
                                <span class="teks-pudar teks-kecil nomor-helper" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; font-size: 11px; pointer-events: none; color: #64748b;">
                                    #${nextNo}
                                </span>
                                <input type="text"
                                       name="nama_helper[]"
                                       class="masukan masukan-helper"
                                       style="padding-left: 38px !important;"
                                       placeholder="Contoh: Helper ${nextNo} (Warga Lokal)"
                                       value=""
                                       maxlength="150">
                            </div>
                            <button type="button"
                                    class="tombol tombol--garis tombol--kecil teks-bahaya btn-hapus-helper"
                                    title="Hapus helper ini">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    `;
                    wadahHelper.insertAdjacentHTML('beforeend', itemHtml);
                    const inputs = wadahHelper.querySelectorAll('.masukan-helper');
                    inputs[inputs.length - 1].focus();
                    perbaruiNomorHelper();
                });

                wadahHelper.addEventListener('click', (e) => {
                    const btn = e.target.closest('.btn-hapus-helper');
                    if (!btn) return;
                    const barisList = wadahHelper.querySelectorAll('.baris-helper');
                    if (barisList.length <= 1) return;
                    btn.closest('.baris-helper').remove();
                    perbaruiNomorHelper();
                });

                perbaruiNomorHelper();
            }

            // Multi-Input Repeater untuk BBM Terhubung (Wajib Dipilih Ada / Tanpa BBM)
            const opsiAdaBbm = document.getElementById('opsiAdaBbm');
            const opsiTanpaBbm = document.getElementById('opsiTanpaBbm');
            const labelOpsiAdaBbm = document.getElementById('labelOpsiAdaBbm');
            const labelOpsiTanpaBbm = document.getElementById('labelOpsiTanpaBbm');
            const formBbm = document.getElementById('formBbmTerhubung');
            const panelTanpaBbm = document.getElementById('panelTanpaBbm');
            const inputKetTanpaBbm = document.getElementById('keterangan_tanpa_bbm');
            const pilihanCepatBbm = document.getElementById('pilihanCepatBbm');
            const wadahInputManualBbm = document.getElementById('wadahInputManualBbm');
            const wadahBbm = document.getElementById('wadahBarisBbm');
            const btnTambahBbm = document.getElementById('btnTambahBarisBbm');

            const masterDaftarBbm = @json($daftarJenisBbm->map(fn($jb) => ['id' => $jb->id, 'nama' => $jb->nama_bbm, 'satuan' => $jb->satuan]));
            const bbmLama = @json(old('bbm_items', $existingBbmItems));

            function sinkronPilihanCepatBbm() {
                if (!pilihanCepatBbm || !inputKetTanpaBbm) return;
                const v = pilihanCepatBbm.value;
                if (v === 'manual') {
                    if (wadahInputManualBbm) wadahInputManualBbm.style.display = 'block';
                    inputKetTanpaBbm.focus();
                } else if (v) {
                    if (wadahInputManualBbm) wadahInputManualBbm.style.display = 'none';
                    inputKetTanpaBbm.value = v;
                } else {
                    if (wadahInputManualBbm) wadahInputManualBbm.style.display = 'none';
                }
            }

            if (pilihanCepatBbm) {
                pilihanCepatBbm.addEventListener('change', sinkronPilihanCepatBbm);
                if (inputKetTanpaBbm && inputKetTanpaBbm.value) {
                    let matched = false;
                    for (let i = 0; i < pilihanCepatBbm.options.length; i++) {
                        if (pilihanCepatBbm.options[i].value === inputKetTanpaBbm.value) {
                            pilihanCepatBbm.selectedIndex = i;
                            matched = true;
                            break;
                        }
                    }
                    if (!matched) {
                        pilihanCepatBbm.value = 'manual';
                        if (wadahInputManualBbm) wadahInputManualBbm.style.display = 'block';
                    }
                }
            }

            function perbaruiTampilanBbm() {
                const adaBbm = opsiAdaBbm && opsiAdaBbm.checked;
                if (labelOpsiAdaBbm) labelOpsiAdaBbm.classList.toggle('terpilih', adaBbm);
                if (labelOpsiTanpaBbm) labelOpsiTanpaBbm.classList.toggle('terpilih', !adaBbm);
                if (formBbm) formBbm.style.display = adaBbm ? 'block' : 'none';
                if (panelTanpaBbm) panelTanpaBbm.style.display = adaBbm ? 'none' : 'block';
                if (inputKetTanpaBbm) {
                    inputKetTanpaBbm.required = !adaBbm;
                    if (adaBbm) {
                        inputKetTanpaBbm.value = '';
                        if (pilihanCepatBbm) pilihanCepatBbm.value = '';
                        if (wadahInputManualBbm) wadahInputManualBbm.style.display = 'none';
                    }
                }
                if (adaBbm && wadahBbm && wadahBbm.children.length === 0) {
                    initBbmRows();
                }
            }

            if (opsiAdaBbm) opsiAdaBbm.addEventListener('change', perbaruiTampilanBbm);
            if (opsiTanpaBbm) opsiTanpaBbm.addEventListener('change', perbaruiTampilanBbm);

            function buatBarisBbm(index, data = {}) {
                const tr = document.createElement('tr');
                tr.className = 'baris-bbm';
                tr.setAttribute('data-index', index);

                let optBbm = '';
                masterDaftarBbm.forEach(jb => {
                    const selected = (data.jenis_bbm_id && String(data.jenis_bbm_id) === String(jb.id)) ? 'selected' : '';
                    optBbm += `<option value="${jb.id}" ${selected}>${jb.nama} (${jb.satuan})</option>`;
                });

                const keperluan = data.keperluan || 'operasional_drone';
                const liter = data.jumlah_liter || '';
                const ket = data.keterangan || '';

                tr.innerHTML = `
                    <td>
                        <select name="bbm_items[${index}][jenis_bbm_id]" class="masukan masukan--kecil" required>
                            ${optBbm}
                        </select>
                    </td>
                    <td>
                        <select name="bbm_items[${index}][keperluan]" class="masukan masukan--kecil" required>
                            <option value="operasional_drone" ${keperluan === 'operasional_drone' ? 'selected' : ''}>Operasional Drone</option>
                            <option value="genset" ${keperluan === 'genset' ? 'selected' : ''}>Genset Lapangan / Charger</option>
                            <option value="mobil" ${keperluan === 'mobil' ? 'selected' : ''}>Kendaraan Tim Lapangan</option>
                            <option value="lainnya" ${keperluan === 'lainnya' ? 'selected' : ''}>Lainnya</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" name="bbm_items[${index}][jumlah_liter]"
                               value="${liter}" class="masukan masukan--kecil angka" placeholder="0.00" required>
                    </td>
                    <td>
                        <input type="text" name="bbm_items[${index}][keterangan]"
                               value="${ket}" class="masukan masukan--kecil" placeholder="Misal: Genset charger petak A01">
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="tombol tombol--garis tombol--bahaya tombol--kecil btnHapusBarisBbm" title="Hapus baris ini">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;

                tr.querySelector('.btnHapusBarisBbm').addEventListener('click', function () {
                    const semuaBaris = wadahBbm.querySelectorAll('.baris-bbm');
                    if (semuaBaris.length > 1) {
                        tr.remove();
                        perbaruiTombolHapusBbm();
                    }
                });

                return tr;
            }

            function perbaruiTombolHapusBbm() {
                if (!wadahBbm) return;
                const semuaBaris = wadahBbm.querySelectorAll('.baris-bbm');
                semuaBaris.forEach((r) => {
                    const btnHapus = r.querySelector('.btnHapusBarisBbm');
                    if (btnHapus) {
                        btnHapus.style.display = semuaBaris.length > 1 ? 'inline-flex' : 'none';
                    }
                });
            }

            let bbmCounter = 0;
            function initBbmRows() {
                if (!wadahBbm) return;
                wadahBbm.innerHTML = '';
                if (Array.isArray(bbmLama) && bbmLama.length > 0) {
                    bbmLama.forEach(item => {
                        wadahBbm.appendChild(buatBarisBbm(bbmCounter++, item));
                    });
                } else {
                    wadahBbm.appendChild(buatBarisBbm(bbmCounter++));
                }
                perbaruiTombolHapusBbm();
            }

            if (btnTambahBbm && wadahBbm) {
                btnTambahBbm.addEventListener('click', function () {
                    wadahBbm.appendChild(buatBarisBbm(bbmCounter++));
                    perbaruiTombolHapusBbm();
                });
            }

            initBbmRows();
            perbaruiTampilanBbm();

            // Pengendalian Screening Kendala Lapangan
            const kondisiLancar = document.getElementById('kondisiLancar');
            const kondisiKendala = document.getElementById('kondisiKendala');
            const labelKondisiLancar = document.getElementById('labelKondisiLancar');
            const labelKondisiKendala = document.getElementById('labelKondisiKendala');
            const panelRincianKendala = document.getElementById('panelRincianKendala');
            const inputCatatanKendala = document.getElementById('catatan_kendala');
            const dampakSebagian = document.getElementById('dampakSebagian');
            const dampakTotal = document.getElementById('dampakTotal');
            const labelDampakSebagian = document.getElementById('labelDampakSebagian');
            const labelDampakTotal = document.getElementById('labelDampakTotal');
            const alertBatalTerbang = document.getElementById('alertBatalTerbang');

            function perbaruiTampilanKendala() {
                const isLancar = kondisiLancar && kondisiLancar.checked;
                const isKendala = kondisiKendala && kondisiKendala.checked;

                if (labelKondisiLancar) labelKondisiLancar.classList.toggle('terpilih', isLancar);
                if (labelKondisiKendala) labelKondisiKendala.classList.toggle('terpilih', isKendala);
                if (panelRincianKendala) panelRincianKendala.style.display = isKendala ? 'block' : 'none';
                if (inputCatatanKendala) {
                    inputCatatanKendala.required = isKendala;
                    if (!isKendala) inputCatatanKendala.value = '';
                }

                const isBatalTotal = isKendala && dampakTotal && dampakTotal.checked;
                if (labelDampakSebagian) labelDampakSebagian.classList.toggle('terpilih', isKendala && !isBatalTotal);
                if (labelDampakTotal) labelDampakTotal.classList.toggle('terpilih', isBatalTotal);
                if (alertBatalTerbang) alertBatalTerbang.style.display = isBatalTotal ? 'flex' : 'none';

                // Atur seluruh baris tabel petak & kunci luas 0.00 Ha jika batal terbang
                const semuaBaris = badanTabel ? badanTabel.querySelectorAll('tr') : [];
                semuaBaris.forEach(row => {
                    const inputStatus = row.querySelector('.input-status-pekerjaan');
                    const inputKet = row.querySelector('.input-ket-kendala');
                    const inputLuas = row.querySelector('.inputLuas');

                    if (inputStatus) {
                        inputStatus.value = isKendala ? 'terkendala' : 'selesai';
                    }
                    if (inputKet && inputCatatanKendala) {
                        inputKet.value = isKendala ? inputCatatanKendala.value : '';
                    }

                    if (inputLuas) {
                        if (isBatalTotal) {
                            inputLuas.value = '0.00';
                            inputLuas.readOnly = true;
                            inputLuas.style.backgroundColor = '#f1f5f9';
                            inputLuas.style.color = '#64748b';
                            inputLuas.style.cursor = 'not-allowed';
                        } else {
                            inputLuas.readOnly = false;
                            inputLuas.style.backgroundColor = '#ffffff';
                            inputLuas.style.color = 'inherit';
                            inputLuas.style.cursor = 'text';
                            if (inputLuas.value === '0.00' && !isBatalTotal) {
                                inputLuas.value = '';
                            }
                        }
                    }
                });

                hitungTotal();
            }

            if (kondisiLancar) kondisiLancar.addEventListener('change', perbaruiTampilanKendala);
            if (kondisiKendala) kondisiKendala.addEventListener('change', perbaruiTampilanKendala);
            if (dampakSebagian) dampakSebagian.addEventListener('change', perbaruiTampilanKendala);
            if (dampakTotal) dampakTotal.addEventListener('change', perbaruiTampilanKendala);

            if (inputCatatanKendala) {
                inputCatatanKendala.addEventListener('input', () => {
                    const semuaBaris = badanTabel ? badanTabel.querySelectorAll('tr') : [];
                    semuaBaris.forEach(row => {
                        const inputKet = row.querySelector('.input-ket-kendala');
                        if (inputKet) inputKet.value = inputCatatanKendala.value;
                    });
                });
            }
            perbaruiTampilanKendala();

            hitungTotal();
        });
    </script>
@endsection
