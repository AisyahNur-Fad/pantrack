@php
    $modeEdit = isset($bbm);
    $pengguna = auth()->user();
    $isAdmin = $pengguna->adalahAdmin();
    $timTerpilih = (string) old('tim_id', $modeEdit ? $bbm->tim_id : request('tim_id', $daftarTim->first()?->id));
    $jenisTxTerpilih = old('jenis_transaksi', $modeEdit ? $bbm->jenis_transaksi : 'penggunaan');
    $keperluanTerpilih = old('keperluan', $modeEdit ? $bbm->keperluan : '');
@endphp

@if (! $modeEdit)
    <input type="hidden" name="nomor_transaksi" value="{{ old('nomor_transaksi') }}">
@endif

{{-- Banner Info Sisa Saldo Tim Real-Time --}}
<div id="banner-saldo-bbm" class="bbm-saldo-banner mb-4">
    <div>
        <div class="teks-kecil teks-pudar">Sisa Saldo Resmi Tim Dipilih</div>
        <div id="banner-saldo-nama" class="teks-tebal teks-utama" style="font-size: 1rem;">—</div>
    </div>
    <div id="banner-saldo-rincian" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <span class="teks-kecil teks-pudar">Memuat saldo…</span>
    </div>
</div>

<div class="grid-form">
    <x-field label="Jenis Transaksi" for="jenis_transaksi" :wajib="true" bantuan="Pilih 'Beli / Stok Masuk (+)' untuk mengisi stok, atau 'Pemakaian BBM (-)' saat memakai BBM tim.">
        <select id="jenis_transaksi" name="jenis_transaksi" class="masukan @error('jenis_transaksi') masukan--error @enderror" required>
            @foreach ($jenisTransaksi as $k => $v)
                <option value="{{ $k }}" @selected($jenisTxTerpilih === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </x-field>

    @if ($daftarTim->count() === 1 && ! $isAdmin)
        @php $satuTim = $daftarTim->first(); @endphp
        <x-field label="Tim Drone" for="tim_id_display">
            <input type="hidden" id="tim_id" name="tim_id" value="{{ $satuTim->id }}">
            <input type="text" id="tim_id_display" class="masukan" value="{{ $satuTim->nama_tim }}" readonly style="background: #f8fafc; cursor: not-allowed;">
        </x-field>
    @else
        <x-field label="Tim Drone" for="tim_id" :wajib="true">
            <select id="tim_id" name="tim_id" class="masukan @error('tim_id') masukan--error @enderror" required>
                @foreach ($daftarTim as $t)
                    <option value="{{ $t->id }}" @selected($timTerpilih === (string) $t->id)>
                        {{ $t->nama_tim }}
                    </option>
                @endforeach
            </select>
        </x-field>
    @endif

    <x-field label="Jenis BBM" for="jenis_bbm_id" :wajib="true">
        <select id="jenis_bbm_id" name="jenis_bbm_id" class="masukan @error('jenis_bbm_id') masukan--error @enderror" required>
            @foreach ($daftarJenisBbm as $jb)
                <option value="{{ $jb->id }}"
                        data-harga="{{ (float) $jb->harga_per_liter }}"
                        @selected((string) old('jenis_bbm_id', $modeEdit ? $bbm->jenis_bbm_id : $daftarJenisBbm->first()?->id) === (string) $jb->id)>
                    {{ $jb->nama_bbm }} (Rp {{ number_format($jb->harga_per_liter, 0, ',', '.') }}/{{ $jb->satuan }})
                </option>
            @endforeach
        </select>
    </x-field>

    <x-field label="Tanggal Transaksi" for="tanggal_transaksi" :wajib="true">
        <input type="date" id="tanggal_transaksi" name="tanggal_transaksi"
               value="{{ old('tanggal_transaksi', $modeEdit && $bbm->tanggal_transaksi ? $bbm->tanggal_transaksi->toDateString() : now()->toDateString()) }}"
               class="masukan @error('tanggal_transaksi') masukan--error @enderror" required>
    </x-field>

    <x-field label="Jumlah (Liter)" for="jumlah_liter" :wajib="true">
        <input type="number" step="0.01" min="0.01" id="jumlah_liter" name="jumlah_liter"
               value="{{ old('jumlah_liter', $modeEdit ? $bbm->jumlah_liter : '') }}"
               class="masukan angka @error('jumlah_liter') masukan--error @enderror"
               placeholder="0.00" required>
    </x-field>

    <x-field label="Keperluan" for="keperluan" bantuan="Otomatis terkunci untuk pengisian stok masuk.">
        <select id="keperluan" name="keperluan" class="masukan @error('keperluan') masukan--error @enderror">
            <option value="">— Pilih Keperluan —</option>
            @foreach (\App\Models\TransaksiBbm::daftarKeperluan() as $k => $v)
                <option value="{{ $k }}" @selected($keperluanTerpilih === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </x-field>
</div>

<x-field label="Keterangan (Opsional)" for="keterangan">
    <input type="text" id="keterangan" name="keterangan"
           value="{{ old('keterangan', $modeEdit ? $bbm->keterangan : '') }}"
           class="masukan @error('keterangan') masukan--error @enderror"
           placeholder="Contoh: Pengisian ulang jeriken posko, atau dipakai charger baterai sesi pagi…">
</x-field>

{{-- Bagian Pembelian & Bukti Nota (otomatis menonjol saat Beli/Stok Masuk/Pengajuan) --}}
<div id="seksi-pembelian-bbm" class="bbm-seksi-nota">
    <div class="flex-antara item-tengah mb-3" style="flex-wrap: wrap; gap: 8px;">
        <div>
            <strong style="font-size: 14px; color: var(--teks, #1e293b);"><i class="bi bi-receipt teks-utama"></i> Rincian Biaya & Bukti Nota</strong>
            <div class="teks-kecil teks-pudar">Total biaya dihitung otomatis berdasarkan harga master Jenis BBM.</div>
        </div>
    </div>

    <div class="grid-form">
        <x-field label="Nomor Struk / Nota SPBU" for="nomor_struk_nota">
            <input type="text" id="nomor_struk_nota" name="nomor_struk_nota"
                   value="{{ old('nomor_struk_nota', $modeEdit ? $bbm->nomor_struk_nota : '') }}"
                   class="masukan" placeholder="Contoh: NOTA-SPBU-8812">
        </x-field>

        <x-field label="Harga Satuan / Liter (Rp)" for="harga_per_liter" :bantuan="$isAdmin ? 'Admin dapat menyesuaikan harga jika berbeda dari master.' : 'Otomatis mengikuti tarif master Admin.'">
            <input type="number" step="any" min="0" id="harga_per_liter" name="harga_per_liter"
                   value="{{ old('harga_per_liter', $modeEdit ? $bbm->harga_per_liter : '') }}"
                   class="masukan angka @error('harga_per_liter') masukan--error @enderror"
                   placeholder="0"
                   @if(! $isAdmin) readonly style="background-color: var(--netral-bg, #f1f5f9); cursor: not-allowed;" @endif>
        </x-field>

        <x-field label="Total Biaya (Rp)" for="total_biaya">
            <input type="number" step="any" min="0" id="total_biaya" name="total_biaya"
                   value="{{ old('total_biaya', $modeEdit ? $bbm->total_biaya : '') }}"
                   class="masukan angka @error('total_biaya') masukan--error @enderror"
                   placeholder="0" readonly style="background-color: var(--netral-bg, #f1f5f9); cursor: not-allowed;">
        </x-field>
    </div>

    @if ($modeEdit && $bbm->lampiran->isNotEmpty())
        <div class="mt-3 mb-3">
            <div class="teks-kecil teks-pudar teks-tebal mb-2">Lampiran Sebelumnya:</div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                @foreach ($bbm->lampiran as $lamp)
                    <a href="{{ route('bbm.lampiran', $lamp) }}" target="_blank" class="bbm-lampiran-chip">
                        <i class="bi bi-paperclip"></i> {{ $lamp->nama_berkas_asli }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-2">
        <x-field :label="$modeEdit ? 'Tambah Foto Struk / Nota Baru (Opsional)' : 'Foto Bukti Struk / Nota (Disarankan untuk Pembelian)'" for="lampiran" bantuan="Format: JPG, PNG, PDF · Maksimal 5 MB per berkas.">
            <input type="file" id="lampiran" name="lampiran[]" class="masukan" multiple accept=".jpg,.jpeg,.png,.pdf">
        </x-field>
        <div id="preview-lampiran-baru" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px;"></div>
    </div>
</div>

@push('gaya')
<style>
    .bbm-saldo-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 14px 18px;
        border-radius: 12px;
        background: linear-gradient(135deg, #f2fbf7 0%, #f8fafc 100%);
        border: 1px solid #bfe7d4;
    }
    .bbm-jenis-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fff;
        border: 1px solid #dbe7e1;
        border-radius: 999px;
        padding: 4px 11px;
        font-size: 12.5px;
    }
    .bbm-seksi-nota {
        padding: 16px 18px;
        border-radius: 12px;
        background: #fafcfb;
        border: 1px solid var(--garis, #e2e8f0);
    }
    .bbm-lampiran-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid var(--garis, #cbd5e1);
        font-size: 12.5px;
        color: var(--hijau-700, #165e45);
        text-decoration: none;
    }
</style>
@endpush

@push('skrip')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const saldoPerTim = @json($saldoPerTim);
        const inputTim = document.getElementById('tim_id');
        const selectJenisBbm = document.getElementById('jenis_bbm_id');
        const selectJenisTransaksi = document.getElementById('jenis_transaksi');
        const inputLiter = document.getElementById('jumlah_liter');
        const inputHarga = document.getElementById('harga_per_liter');
        const inputTotal = document.getElementById('total_biaya');
        const selectKeperluan = document.getElementById('keperluan');
        const seksiPembelian = document.getElementById('seksi-pembelian-bbm');
        const inputLampiran = document.getElementById('lampiran');
        const previewLampiran = document.getElementById('preview-lampiran-baru');
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};

        function renderBannerSaldo() {
            const namaEl = document.getElementById('banner-saldo-nama');
            const rincianEl = document.getElementById('banner-saldo-rincian');
            if (!inputTim || !namaEl || !rincianEl) return;

            const dataTim = saldoPerTim[inputTim.value];
            if (!dataTim) {
                namaEl.textContent = '—';
                rincianEl.innerHTML = '<span class="teks-kecil teks-pudar">Saldo belum tersedia</span>';
                return;
            }

            namaEl.textContent = dataTim.nama_tim;
            const list = Array.isArray(dataTim.saldo_per_jenis) ? dataTim.saldo_per_jenis : [];
            if (!list.length) {
                rincianEl.innerHTML = '<span class="teks-kecil teks-pudar">0,0 L</span>';
                return;
            }

            rincianEl.innerHTML = list.map((pj) => {
                const angka = Number(pj.saldo || 0).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                const cls = pj.saldo < 0 ? 'teks-bahaya' : (pj.saldo > 0 ? 'teks-utama' : 'teks-pudar');
                return `<span class="bbm-jenis-pill"><span class="teks-pudar">${pj.nama_bbm}:</span> <strong class="${cls}">${angka} L</strong></span>`;
            }).join('');
        }

        function syncHargaDanTotal() {
            if (selectJenisBbm && inputHarga) {
                const opt = selectJenisBbm.options[selectJenisBbm.selectedIndex];
                const hargaMaster = opt ? parseFloat(opt.getAttribute('data-harga') || 0) : 0;
                if (!isAdmin || !inputHarga.value || inputHarga.value === '0') {
                    if (hargaMaster > 0) {
                        inputHarga.value = hargaMaster;
                    }
                }
            }
            hitungTotal();
        }

        function hitungTotal() {
            if (!inputLiter || !inputHarga || !inputTotal) return;
            const liter = parseFloat(inputLiter.value) || 0;
            const harga = parseFloat(inputHarga.value) || 0;
            if (liter > 0 && harga > 0) {
                inputTotal.value = Math.round(liter * harga);
            } else {
                inputTotal.value = '';
            }
        }

        function syncTampilanJenisTransaksi() {
            if (!selectJenisTransaksi) return;
            const valTx = selectJenisTransaksi.value;

            if (selectKeperluan) {
                if (valTx === 'stok_awal' || valTx === 'penerimaan') {
                    selectKeperluan.value = 'stok_masuk';
                    selectKeperluan.style.pointerEvents = 'none';
                    selectKeperluan.style.backgroundColor = 'var(--netral-bg, #f1f5f9)';
                    selectKeperluan.removeAttribute('required');
                } else if (valTx === 'penggunaan') {
                    selectKeperluan.style.pointerEvents = 'auto';
                    selectKeperluan.style.backgroundColor = '#fff';
                    selectKeperluan.setAttribute('required', 'required');
                    if (!selectKeperluan.value || selectKeperluan.value === 'stok_masuk') {
                        selectKeperluan.value = 'operasional_drone';
                    }
                } else {
                    selectKeperluan.style.pointerEvents = 'auto';
                    selectKeperluan.style.backgroundColor = '#fff';
                    selectKeperluan.removeAttribute('required');
                }
            }

            if (seksiPembelian) {
                seksiPembelian.style.display = (valTx === 'penggunaan') ? 'none' : 'block';
            }
        }

        if (inputTim && inputTim.tagName === 'SELECT') {
            inputTim.addEventListener('change', renderBannerSaldo);
        }

        if (selectJenisBbm) {
            selectJenisBbm.addEventListener('change', function () {
                const opt = selectJenisBbm.options[selectJenisBbm.selectedIndex];
                const hargaMaster = opt ? parseFloat(opt.getAttribute('data-harga') || 0) : 0;
                if (inputHarga && (!isAdmin || hargaMaster > 0)) {
                    inputHarga.value = hargaMaster > 0 ? hargaMaster : '';
                }
                hitungTotal();
            });
        }

        if (inputLiter) inputLiter.addEventListener('input', hitungTotal);
        if (inputHarga) inputHarga.addEventListener('input', hitungTotal);
        if (selectJenisTransaksi) selectJenisTransaksi.addEventListener('change', syncTampilanJenisTransaksi);

        if (inputLampiran && previewLampiran) {
            inputLampiran.addEventListener('change', function () {
                previewLampiran.innerHTML = '';
                Array.from(inputLampiran.files || []).forEach((file) => {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        img.alt = file.name;
                        img.style.cssText = 'width:84px;height:84px;object-fit:cover;border-radius:8px;border:1px solid #cbd5e1;';
                        previewLampiran.appendChild(img);
                    } else {
                        const chip = document.createElement('span');
                        chip.className = 'bbm-lampiran-chip';
                        chip.textContent = file.name;
                        previewLampiran.appendChild(chip);
                    }
                });
            });
        }

        renderBannerSaldo();
        syncHargaDanTotal();
        syncTampilanJenisTransaksi();
    });
</script>
@endpush

