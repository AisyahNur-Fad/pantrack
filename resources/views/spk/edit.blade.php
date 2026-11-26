@extends('layouts.app')

@section('judul', 'Ubah SPK: ' . $spk->nomor_spk)

@section('konten')
    <x-halaman-kepala
        :judul="'Ubah SPK: ' . $spk->nomor_spk"
        deskripsi="Perbaiki data target operasional atau nomor SPK sesuai instruksi kepala lapangan."
        ikon="bi-pencil-square">
        <a href="{{ route('spk.show', $spk) }}" class="tombol tombol--garis">
            <i class="bi bi-arrow-left"></i> Kembali ke Detail
        </a>
    </x-halaman-kepala>

    <div class="kartu">
        <form method="POST" action="{{ route('spk.update', $spk) }}" enctype="multipart/form-data" class="alur-vertikal" id="formEditSpk">
            @csrf
            @method('PUT')

            <div class="grid-form">
                <x-field label="Nomor SPK" for="nomor_spk" :wajib="true">
                    <input type="text" id="nomor_spk" name="nomor_spk" value="{{ old('nomor_spk', $spk->nomor_spk) }}"
                           class="masukan @error('nomor_spk') masukan--error @enderror" required>
                </x-field>

                <x-field label="Tanggal SPK" for="tanggal_spk" :wajib="true">
                    <input type="date" id="tanggal_spk" name="tanggal_spk"
                           value="{{ old('tanggal_spk', $spk->tanggal_spk?->toDateString()) }}"
                           class="masukan @error('tanggal_spk') masukan--error @enderror" required>
                </x-field>

                {{-- 1. Dropdown Perusahaan (PT) Induk --}}
                <x-field label="Pilih Perusahaan (PT)" for="pt_id" :wajib="true">
                    @php
                        $currentPtId = old('pt_id', $selectedPtId ?? $spk->distrik?->pt_id);
                    @endphp
                    <select id="pt_id" name="pt_id" class="masukan @error('pt_id') masukan--error @enderror" required>
                        <option value="">— Pilih PT —</option>
                        @foreach ($daftarPt as $p)
                            <option value="{{ $p->id }}" @selected($currentPtId == $p->id)>
                                {{ $p->kode_pt ? '[' . $p->kode_pt . '] ' : '' }}{{ $p->nama_pt }}
                            </option>
                        @endforeach
                    </select>
                </x-field>

                {{-- 2. Dropdown Distrik (Cascading dari pilihan PT) --}}
                <x-field label="Pilih Distrik" for="distrik_id" :wajib="true">
                    <div>
                        <select id="distrik_id" name="distrik_id"
                                class="masukan @error('distrik_id') masukan--error @enderror"
                                required
                                {{ empty($currentPtId) ? 'disabled' : '' }}>
                            <option value="">{{ empty($currentPtId) ? '— Pilih PT Terlebih Dahulu —' : '— Pilih Distrik —' }}</option>
                            @foreach ($daftarDistrik as $d)
                                @if (!empty($currentPtId) && $d->pt_id == $currentPtId)
                                    <option value="{{ $d->id }}" @selected(old('distrik_id', $spk->distrik_id) == $d->id)>
                                        {{ $d->nama_distrik }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        {{-- Indikator Loading Saat Fetch API --}}
                        <div id="loadingDistrik" class="indikator-muat-distrik" style="display: none;">
                            <span class="animasi-putar"><i class="bi bi-arrow-repeat"></i></span>
                            <span>Memuat data distrik dari server...</span>
                        </div>
                    </div>
                </x-field>

                <x-field label="Penugasan Tim Lapangan" for="tim_id" bantuan="Pilih tim khusus atau biarkan kosong jika terbuka untuk semua tim di distrik ini.">
                    <select id="tim_id" name="tim_id" class="masukan @error('tim_id') masukan--error @enderror">
                        <option value="">— Terbuka untuk Semua Tim —</option>
                        @foreach ($daftarTim as $t)
                            @php
                                $isPerawatan = ($t->drone?->status_operasional === \App\Models\Drone::STATUS_PERAWATAN);
                            @endphp
                            <option value="{{ $t->id }}" @selected(old('tim_id', $spk->tim_id) == $t->id) @disabled($isPerawatan && $spk->tim_id !== $t->id)>
                                {{ $t->nama_tim }} @if($isPerawatan) (⚠️ Drone {{ $t->drone?->nomor_drone }} sedang PERAWATAN) @endif
                            </option>
                        @endforeach
                    </select>
                </x-field>

                <x-field label="Jenis Pekerjaan Target" for="jenis_pekerjaan_id">
                    <select id="jenis_pekerjaan_id" name="jenis_pekerjaan_id" class="masukan @error('jenis_pekerjaan_id') masukan--error @enderror">
                        <option value="">Semua Jenis / Campuran</option>
                        @foreach ($daftarPekerjaan as $p)
                            <option value="{{ $p->id }}" @selected(old('jenis_pekerjaan_id', $spk->jenis_pekerjaan_id) == $p->id)>{{ $p->nama_pekerjaan }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field label="Periode Bulan" for="periode_bulan" :wajib="true">
                    <select id="periode_bulan" name="periode_bulan" class="masukan @error('periode_bulan') masukan--error @enderror" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(old('periode_bulan', $spk->periode_bulan) == $m)>
                                {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </x-field>

                <x-field label="Periode Tahun" for="periode_tahun" :wajib="true">
                    <select id="periode_tahun" name="periode_tahun" class="masukan @error('periode_tahun') masukan--error @enderror" required>
                        @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" @selected(old('periode_tahun', $spk->periode_tahun) == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </x-field>
            </div>

            <div class="grid-form">
                <x-field label="Ganti Lampiran SPK (Opsional, PDF / Gambar)" for="berkas_spk" bantuan="Kosongkan jika tidak ingin mengubah berkas yang sudah ada.">
                    <input type="file" id="berkas_spk" name="berkas_spk" class="masukan @error('berkas_spk') masukan--error @enderror" accept=".pdf,.jpg,.jpeg,.png">
                </x-field>

                <x-field label="Keterangan / Catatan SPK" for="keterangan">
                    <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan', $spk->keterangan) }}" class="masukan">
                </x-field>
            </div>

            <hr class="garis-pemisah">

            <div>
                <div class="flex-antara item-tengah mb-3">
                    <div>
                        <h3 class="judul-sub">Daftar Petak Target (detail_spk)</h3>
                        <p class="teks-pudar teks-kecil">Ubah atau sesuaikan petak target dan luasan hektar (Ha).</p>
                    </div>
                    <div class="flex-tengah gap-2">
                        <button type="button" class="tombol tombol--garis tombol--kecil" id="toggleSemuaMapping" title="Centang / Batal Semua Mapping">
                            <i class="bi bi-map"></i> Centang Semua Mapping
                        </button>
                        <button type="button" class="tombol tombol--garis tombol--kecil" id="tambahBarisPetak">
                            <i class="bi bi-plus-circle"></i> Tambah Petak Target
                        </button>
                    </div>
                </div>

                @error('petak')
                    <div class="alert alert--bahaya mb-3">{{ $message }}</div>
                @enderror

                <div class="tabel-bungkus">
                    <table class="tabel" id="tabelPetakTarget">
                        <thead>
                            <tr>
                                <th style="width: 32%">Pilih Petak Target *</th>
                                <th style="width: 18%" class="angka">Luas Target (Ha) *</th>
                                <th style="width: 22%; text-align: center;">Layanan Mapping (+Rp {{ number_format($tarifMappingDefault, 0, ',', '.') }}/Ha)</th>
                                <th style="width: 20%">Catatan</th>
                                <th style="width: 8%" class="aksi">Hapus</th>
                            </tr>
                        </thead>
                        <tbody id="badanTabelPetak">
                            @php
                                $detailLama = $spk->detail->map(fn($d) => [
                                    'petak_id' => $d->petak_id,
                                    'nomor_petak_baru' => '',
                                    'luas_target_ha' => $d->luas_target_ha,
                                    'perlu_mapping' => $d->perlu_mapping ? 1 : 0,
                                    'catatan' => $d->catatan,
                                ])->toArray();
                                $oldPetak = old('petak', !empty($detailLama) ? $detailLama : [['petak_id' => '', 'nomor_petak_baru' => '', 'luas_target_ha' => '', 'perlu_mapping' => 0, 'catatan' => '']]);
                            @endphp
                            @foreach ($oldPetak as $i => $row)
                                <tr>
                                    <td>
                                        {{-- Mode Dropdown Petak Terdaftar --}}
                                        <div class="wadah-petak-select" @if(!empty($row['nomor_petak_baru'])) style="display: none;" @endif>
                                            <select name="petak[{{ $i }}][petak_id]" class="masukan select-petak" @if(empty($row['nomor_petak_baru'])) required @endif>
                                                <option value="">Pilih Petak</option>
                                                @foreach ($daftarPetak as $ptk)
                                                    <option value="{{ $ptk->id }}"
                                                            data-distrik="{{ $ptk->distrik_id }}"
                                                            @selected(($row['petak_id'] ?? '') == $ptk->id)>
                                                        {{ $ptk->kode_petak }} {{ $ptk->nama_petak ? '— '.$ptk->nama_petak : '' }} ({{ $ptk->distrik?->nama_distrik }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div style="margin-top: 4px;">
                                                <button type="button" class="btn-alih-ke-baru" style="background: none; border: none; padding: 0; color: #059669; font-size: 0.775rem; font-weight: 600; cursor: pointer;">
                                                    <i class="bi bi-pencil-square"></i> Petak belum ada di daftar? Ketik Baru (cth: B7140)
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Mode Ketik Langsung Nomor Petak Baru --}}
                                        <div class="wadah-petak-baru" @if(empty($row['nomor_petak_baru'])) style="display: none;" @endif>
                                            <div style="display: flex; align-items: stretch;">
                                                <span class="badge-prefix-distrik" title="Singkatan kode distrik otomatis" style="background: #e2e8f0; border: 1px solid #cbd5e1; border-right: none; padding: 0.35rem 0.6rem; font-weight: 700; color: #1e293b; border-radius: 6px 0 0 6px; font-size: 0.85rem; display: flex; align-items: center; min-width: 44px; justify-content: center;">--</span>
                                                <input type="text" name="petak[{{ $i }}][nomor_petak_baru]" value="{{ $row['nomor_petak_baru'] ?? '' }}"
                                                       class="masukan input-petak-baru" placeholder="B7140"
                                                       style="border-radius: 0 6px 6px 0; text-transform: uppercase;" @if(!empty($row['nomor_petak_baru'])) required @endif>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                                <span class="teks-pudar teks-kecil">Otomatis tersimpan: <strong class="preview-petak-teks" style="color: #0f766e;">{{ !empty($row['nomor_petak_baru']) ? $row['nomor_petak_baru'] : '—' }}</strong></span>
                                                <button type="button" class="btn-alih-ke-select" style="background: none; border: none; padding: 0; color: #2563eb; font-size: 0.775rem; font-weight: 600; cursor: pointer;">
                                                    <i class="bi bi-list-ul"></i> Pilih dari daftar
                                                </button>
                                            </div>
                                        </div>
                                        @error("petak.{$i}.petak_id")
                                            <div class="teks-bahaya teks-kecil mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="petak[{{ $i }}][luas_target_ha]"
                                               value="{{ $row['luas_target_ha'] ?? '' }}" class="masukan angka input-luas-petak" placeholder="0.00" required>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <label class="centang-inline" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.825rem; font-weight: 500;">
                                            <input type="checkbox" name="petak[{{ $i }}][perlu_mapping]" value="1" class="check-mapping" @checked(!empty($row['perlu_mapping']))>
                                            <span>🗺️ Butuh Mapping</span>
                                        </label>
                                    </td>
                                    <td>
                                        <input type="text" name="petak[{{ $i }}][catatan]" value="{{ $row['catatan'] ?? '' }}"
                                               class="masukan" placeholder="Catatan opsional…">
                                    </td>
                                    <td class="aksi">
                                        <button type="button" class="tombol tombol--garis tombol--kecil tombol--ikon tombolHapusBaris" title="Hapus Baris">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Kotak Live Kalkulator Estimasi Anggaran & Target --}}
                <div class="kartu" style="margin-top: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            <div>
                                <span class="teks-pudar teks-kecil" style="display: block;">Total Target Luas:</span>
                                <strong id="ringkasanTotalLuas" style="font-size: 1.15rem; color: #1e293b;">0.00 Ha</strong>
                            </div>
                            <div>
                                <span class="teks-pudar teks-kecil" style="display: block;">Target Perlu Mapping:</span>
                                <strong id="ringkasanLuasMapping" style="font-size: 1.15rem; color: #0284c7;">0.00 Ha</strong>
                            </div>
                            <div>
                                <span class="teks-pudar teks-kecil" style="display: block;">Tarif Pokok Drone:</span>
                                <span id="ringkasanTarifPokok" style="font-weight: 600; color: #059669;">Rp 0 / Ha</span>
                            </div>
                            <div>
                                <span class="teks-pudar teks-kecil" style="display: block;">Tarif Mapping Add-on:</span>
                                <span id="ringkasanTarifMapping" style="font-weight: 600; color: #0284c7;">Rp {{ number_format($tarifMappingDefault, 0, ',', '.') }} / Ha</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="teks-pudar teks-kecil" style="display: block;">Estimasi Anggaran Target SPK:</span>
                            <strong id="ringkasanTotalBiaya" style="font-size: 1.35rem; color: #059669; font-weight: 800;">Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bilah-aksi">
                <button type="submit" class="tombol tombol--utama">
                    <i class="bi bi-check2-circle"></i> Simpan Perubahan SPK
                </button>
                <a href="{{ route('spk.show', $spk) }}" class="tombol tombol--garis">Batal</a>
            </div>
        </form>
    </div>

    {{-- Template baris dinamis petak --}}
    <template id="tmplBarisPetak">
        <tr>
            <td>
                {{-- Mode Dropdown Petak Terdaftar --}}
                <div class="wadah-petak-select">
                    <select name="petak[INDEX][petak_id]" class="masukan select-petak" required>
                        <option value="">Pilih Petak</option>
                        @foreach ($daftarPetak as $ptk)
                            <option value="{{ $ptk->id }}" data-distrik="{{ $ptk->distrik_id }}">{{ $ptk->kode_petak }} {{ $ptk->nama_petak ? '— '.$ptk->nama_petak : '' }} ({{ $ptk->distrik?->nama_distrik }})</option>
                        @endforeach
                    </select>
                    <div style="margin-top: 4px;">
                        <button type="button" class="btn-alih-ke-baru" style="background: none; border: none; padding: 0; color: #059669; font-size: 0.775rem; font-weight: 600; cursor: pointer;">
                            <i class="bi bi-pencil-square"></i> Petak belum ada di daftar? Ketik Baru (cth: B7140)
                        </button>
                    </div>
                </div>

                {{-- Mode Ketik Langsung Nomor Petak Baru --}}
                <div class="wadah-petak-baru" style="display: none;">
                    <div style="display: flex; align-items: stretch;">
                        <span class="badge-prefix-distrik" title="Singkatan kode distrik otomatis" style="background: #e2e8f0; border: 1px solid #cbd5e1; border-right: none; padding: 0.35rem 0.6rem; font-weight: 700; color: #1e293b; border-radius: 6px 0 0 6px; font-size: 0.85rem; display: flex; align-items: center; min-width: 44px; justify-content: center;">--</span>
                        <input type="text" name="petak[INDEX][nomor_petak_baru]"
                               class="masukan input-petak-baru" placeholder="B7140"
                               style="border-radius: 0 6px 6px 0; text-transform: uppercase;">
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                        <span class="teks-pudar teks-kecil">Otomatis tersimpan: <strong class="preview-petak-teks" style="color: #0f766e;">—</strong></span>
                        <button type="button" class="btn-alih-ke-select" style="background: none; border: none; padding: 0; color: #2563eb; font-size: 0.775rem; font-weight: 600; cursor: pointer;">
                            <i class="bi bi-list-ul"></i> Pilih dari daftar
                        </button>
                    </div>
                </div>
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" name="petak[INDEX][luas_target_ha]" class="masukan angka input-luas-petak" placeholder="0.00" required>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <label class="centang-inline" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.825rem; font-weight: 500;">
                    <input type="checkbox" name="petak[INDEX][perlu_mapping]" value="1" class="check-mapping">
                    <span>🗺️ Butuh Mapping</span>
                </label>
            </td>
            <td>
                <input type="text" name="petak[INDEX][catatan]" class="masukan" placeholder="Catatan opsional…">
            </td>
            <td class="aksi">
                <button type="button" class="tombol tombol--garis tombol--kecil tombol--ikon tombolHapusBaris" title="Hapus Baris">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    <style>
        .indikator-muat-distrik {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.35rem;
            font-size: 0.775rem;
            color: #047857;
            font-weight: 500;
            background: rgba(16, 185, 129, 0.12);
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            animation: fadeInDistrik 0.2s ease-in-out;
        }
        .animasi-putar {
            display: inline-block;
            animation: spinDistrik 0.8s linear infinite;
        }
        @keyframes spinDistrik {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes fadeInDistrik {
            from { opacity: 0; transform: translateY(-3px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ==========================================
            // 1. STATE MANAGEMENT CASCADING PT -> DISTRIK
            // ==========================================
            const ptSelect = document.getElementById('pt_id');
            const distrikSelect = document.getElementById('distrik_id');
            const loadingDistrik = document.getElementById('loadingDistrik');
            let distrikTerpilih = "{{ old('distrik_id', $spk->distrik_id) }}";

            /**
             * Tampilkan atau sembunyikan indikator loading
             */
            function setDistrikLoading(sedangMemuat) {
                if (sedangMemuat) {
                    distrikSelect.disabled = true;
                    loadingDistrik.style.display = 'inline-flex';
                    distrikSelect.innerHTML = '<option value="">— Memuat data distrik... —</option>';
                } else {
                    loadingDistrik.style.display = 'none';
                }
            }

            /**
             * Fetch distrik dari API endpoint berdasarkan pt_id terpilih
             */
            async function ambilDistrikPerPt(ptId, targetDistrikId = '') {
                if (!ptId) {
                    distrikSelect.innerHTML = '<option value="">— Pilih PT Terlebih Dahulu —</option>';
                    distrikSelect.disabled = true;
                    distrikSelect.value = '';
                    filterPetakBerdasarkanDistrik('');
                    return;
                }

                setDistrikLoading(true);

                try {
                    const response = await fetch(`/api/distrik?pt_id=${encodeURIComponent(ptId)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const json = await response.json();
                    const listDistrik = json.data || [];

                    distrikSelect.innerHTML = '<option value="">— Pilih Distrik —</option>';

                    if (listDistrik.length === 0) {
                        distrikSelect.innerHTML = '<option value="">— Tidak ada distrik terdaftar untuk PT ini —</option>';
                        distrikSelect.disabled = true;
                    } else {
                        listDistrik.forEach(d => {
                            const opt = document.createElement('option');
                            opt.value = d.id;
                            opt.textContent = d.nama_distrik;
                            if (targetDistrikId && String(d.id) === String(targetDistrikId)) {
                                opt.selected = true;
                            }
                            distrikSelect.appendChild(opt);
                        });
                        distrikSelect.disabled = false;
                    }

                    // Sinkronisasi opsi petak target dengan distrik yang terpilih
                    filterPetakBerdasarkanDistrik(distrikSelect.value);
                    updateSemuaBadgePrefix();
                } catch (error) {
                    console.error('Gagal mengambil data distrik:', error);
                    distrikSelect.innerHTML = '<option value="">— Gagal memuat distrik. Coba pilih ulang PT —</option>';
                    distrikSelect.disabled = false;
                } finally {
                    setDistrikLoading(false);
                }
            }

            // Event saat pilihan PT berubah oleh user
            ptSelect.addEventListener('change', function () {
                const ptId = this.value;
                // Kebutuhan spesifikasi: Ketika user mengubah PT, reset pilihan Distrik yang sudah terpilih
                distrikTerpilih = '';
                distrikSelect.value = '';
                ambilDistrikPerPt(ptId, '');
            });

            // Event saat pilihan distrik berubah -> filter petak
            distrikSelect.addEventListener('change', function () {
                filterPetakBerdasarkanDistrik(this.value);
                updateSemuaBadgePrefix();
            });

            // Inisialisasi awal saat edit dimuat: jika belum ada list distrik pada select, muat dari API
            if (ptSelect.value && (!distrikSelect.options.length || distrikSelect.options.length <= 1)) {
                ambilDistrikPerPt(ptSelect.value, distrikTerpilih);
            }

            // ==========================================
            // 2. FILTERING & PREFIX KODE PETAK LAPANGAN
            // ==========================================
            const mapSingkatanDistrik = @json($mapSingkatanDistrik ?? []);

            function getPrefixDistrikTerpilih() {
                const dId = distrikSelect ? distrikSelect.value : '';
                return (dId && mapSingkatanDistrik[dId]) ? mapSingkatanDistrik[dId] : '--';
            }

            function updateRowPetakPreview(row) {
                if (!row) return;
                const prefix = getPrefixDistrikTerpilih();
                const inputBaru = row.querySelector('.input-petak-baru');
                const previewTeks = row.querySelector('.preview-petak-teks');
                if (!inputBaru || !previewTeks) return;
                const rawVal = inputBaru.value.trim().toUpperCase().replace(/[^A-Z0-9_-]/g, '');
                if (!rawVal) {
                    previewTeks.textContent = prefix !== '--' ? (prefix + '...') : '—';
                    return;
                }
                if (prefix !== '--') {
                    if (rawVal.startsWith(prefix)) {
                        previewTeks.textContent = rawVal;
                    } else {
                        previewTeks.textContent = prefix + rawVal;
                    }
                } else {
                    previewTeks.textContent = rawVal;
                }
            }

            function updateSemuaBadgePrefix() {
                const prefix = getPrefixDistrikTerpilih();
                document.querySelectorAll('.badge-prefix-distrik').forEach(el => {
                    el.textContent = prefix;
                });
                document.querySelectorAll('#badanTabelPetak tr').forEach(row => {
                    updateRowPetakPreview(row);
                });
            }

            function filterPetakBerdasarkanDistrik(distrikId) {
                const allPetakSelects = document.querySelectorAll('#badanTabelPetak .select-petak');
                allPetakSelects.forEach(sel => {
                    const currentVal = sel.value;
                    let matchFound = false;

                    Array.from(sel.options).forEach(opt => {
                        if (!opt.value) return;
                        const optDistrik = opt.getAttribute('data-distrik');
                        if (!distrikId || optDistrik === String(distrikId)) {
                            opt.hidden = false;
                            opt.disabled = false;
                            if (opt.value === currentVal) matchFound = true;
                        } else {
                            opt.hidden = true;
                            opt.disabled = true;
                        }
                    });

                    // Jika petak terpilih tidak sesuai dengan distrik yang aktif, kosongkan
                    if (currentVal && !matchFound) {
                        sel.value = '';
                    }
                });
            }

            // ==========================================
            // 3. LOGIKA REPEATER BARIS PETAK TARGET
            // ==========================================
            let index = {{ count($oldPetak) }};
            const badanTabel = document.getElementById('badanTabelPetak');
            const tmpl = document.getElementById('tmplBarisPetak').innerHTML;

            document.getElementById('tambahBarisPetak').addEventListener('click', () => {
                const html = tmpl.replace(/INDEX/g, index++);
                badanTabel.insertAdjacentHTML('beforeend', html);
                filterPetakBerdasarkanDistrik(distrikSelect.value);
                updateSemuaBadgePrefix();
            });

            badanTabel.addEventListener('click', (e) => {
                const btn = e.target.closest('.tombolHapusBaris');
                if (btn) {
                    const rows = badanTabel.querySelectorAll('tr');
                    if (rows.length <= 1) {
                        alert('Minimal satu baris petak target harus dipertahankan.');
                        return;
                    }
                    btn.closest('tr').remove();
                    hitungEstimasiAnggaran();
                    return;
                }

                const btnKeBaru = e.target.closest('.btn-alih-ke-baru');
                if (btnKeBaru) {
                    const td = btnKeBaru.closest('td');
                    const wadahSelect = td.querySelector('.wadah-petak-select');
                    const wadahBaru = td.querySelector('.wadah-petak-baru');
                    const sel = td.querySelector('.select-petak');
                    const inp = td.querySelector('.input-petak-baru');

                    wadahSelect.style.display = 'none';
                    wadahBaru.style.display = 'block';
                    sel.value = '';
                    sel.required = false;
                    inp.required = true;
                    inp.focus();
                    updateRowPetakPreview(td.closest('tr'));
                    return;
                }

                const btnKeSelect = e.target.closest('.btn-alih-ke-select');
                if (btnKeSelect) {
                    const td = btnKeSelect.closest('td');
                    const wadahSelect = td.querySelector('.wadah-petak-select');
                    const wadahBaru = td.querySelector('.wadah-petak-baru');
                    const sel = td.querySelector('.select-petak');
                    const inp = td.querySelector('.input-petak-baru');

                    wadahBaru.style.display = 'none';
                    wadahSelect.style.display = 'block';
                    inp.value = '';
                    inp.required = false;
                    sel.required = true;
                    sel.focus();
                    return;
                }
            });

            // ==========================================
            // 4. LOGIKA LIVE KALKULATOR ESTIMASI ANGGARAN & MAPPING
            // ==========================================
            const mapTarifPekerjaan = @json($mapTarifPekerjaan ?? []);
            const tarifMappingPerHa = {{ (float) ($tarifMappingDefault ?? 30000) }};
            const pekerjaanSelect = document.getElementById('jenis_pekerjaan_id');

            const ringkasanTotalLuas = document.getElementById('ringkasanTotalLuas');
            const ringkasanLuasMapping = document.getElementById('ringkasanLuasMapping');
            const ringkasanTarifPokok = document.getElementById('ringkasanTarifPokok');
            const ringkasanTotalBiaya = document.getElementById('ringkasanTotalBiaya');
            const btnToggleSemuaMapping = document.getElementById('toggleSemuaMapping');

            function formatRupiah(angka) {
                return 'Rp ' + Number(angka).toLocaleString('id-ID');
            }

            function hitungEstimasiAnggaran() {
                const jenisId = pekerjaanSelect ? pekerjaanSelect.value : '';
                const tarifPokok = jenisId && mapTarifPekerjaan[jenisId] ? parseFloat(mapTarifPekerjaan[jenisId]) : 0;

                let totalLuas = 0;
                let luasMapping = 0;

                const rows = badanTabel.querySelectorAll('tr');
                rows.forEach(row => {
                    const inputLuas = row.querySelector('.input-luas-petak');
                    const checkMap = row.querySelector('.check-mapping');
                    const luas = inputLuas ? parseFloat(inputLuas.value) || 0 : 0;
                    totalLuas += luas;
                    if (checkMap && checkMap.checked) {
                        luasMapping += luas;
                    }
                });

                const biayaPokok = totalLuas * tarifPokok;
                const biayaMapping = luasMapping * tarifMappingPerHa;
                const totalBiaya = biayaPokok + biayaMapping;

                if (ringkasanTotalLuas) ringkasanTotalLuas.textContent = totalLuas.toFixed(2) + ' Ha';
                if (ringkasanLuasMapping) ringkasanLuasMapping.textContent = luasMapping.toFixed(2) + ' Ha';
                if (ringkasanTarifPokok) ringkasanTarifPokok.textContent = tarifPokok > 0 ? (formatRupiah(tarifPokok) + ' / Ha') : 'Rp 0 / Ha';
                if (ringkasanTotalBiaya) ringkasanTotalBiaya.textContent = formatRupiah(totalBiaya);
            }

            if (pekerjaanSelect) {
                pekerjaanSelect.addEventListener('change', hitungEstimasiAnggaran);
            }

            badanTabel.addEventListener('input', (e) => {
                if (e.target.classList.contains('input-petak-baru')) {
                    updateRowPetakPreview(e.target.closest('tr'));
                }
                hitungEstimasiAnggaran();
            });
            badanTabel.addEventListener('change', hitungEstimasiAnggaran);

            if (btnToggleSemuaMapping) {
                btnToggleSemuaMapping.addEventListener('click', () => {
                    const allCheckboxes = badanTabel.querySelectorAll('.check-mapping');
                    const adaYangBelum = Array.from(allCheckboxes).some(cb => !cb.checked);
                    allCheckboxes.forEach(cb => {
                        cb.checked = adaYangBelum;
                    });
                    btnToggleSemuaMapping.innerHTML = adaYangBelum
                        ? '<i class="bi bi-x-circle"></i> Batal Semua Mapping'
                        : '<i class="bi bi-map"></i> Centang Semua Mapping';
                    hitungEstimasiAnggaran();
                });
            }

            hitungEstimasiAnggaran();

            // Inisialisasi awal filter petak sesuai distrik SPK
            if (distrikSelect.value) {
                filterPetakBerdasarkanDistrik(distrikSelect.value);
            }
            updateSemuaBadgePrefix();
        });
    </script>
@endsection
