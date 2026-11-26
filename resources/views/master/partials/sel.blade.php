{{-- Satu sel tabel master. $r = baris (model), $k = definisi kolom dari controller. --}}
@php
    $tipe = $k['tipe'] ?? 'teks';
    $nilai = data_get($r, $k['key']);
    $kosong = is_null($nilai) || $nilai === '';
    $isKolomStatus = ($tipe !== 'status_drone')
                     && in_array($k['key'], ['status_operasional', 'aktif', 'status_petak', 'status_tim'])
                     && Route::has($prefix.'.toggle-status');
@endphp

@if ($isKolomStatus)
    @php
        $isAktif = false;
        $statusLabel = 'Nonaktif';
        $statusWarna = 'netral';

        if ($k['key'] === 'status_operasional') {
            $isAktif = ($nilai === \App\Models\Drone::STATUS_AKTIF);
            $statusLabel = \App\Models\Drone::statusOperasional()[$nilai] ?? ($isAktif ? 'Aktif' : 'Tidak beroperasi');
            $statusWarna = $isAktif ? 'sukses' : ($nilai === \App\Models\Drone::STATUS_PERAWATAN ? 'peringatan' : 'bahaya');
        } elseif ($k['key'] === 'status_petak') {
            $isAktif = ($nilai === \App\Models\Petak::STATUS_AKTIF);
            $statusLabel = \App\Models\Petak::statusPetak()[$nilai] ?? ($isAktif ? 'Aktif' : 'Tidak aktif');
            $statusWarna = $isAktif ? 'sukses' : 'netral';
        } elseif ($k['key'] === 'status_tim') {
            $isAktif = ($nilai === \App\Models\Tim::STATUS_AKTIF);
            $statusLabel = \App\Models\Tim::statusTim()[$nilai] ?? ($isAktif ? 'Aktif' : 'Tidak aktif');
            $statusWarna = $isAktif ? 'sukses' : 'netral';
        } else {
            $isAktif = (bool) $nilai;
            $statusLabel = $isAktif ? 'Aktif' : 'Nonaktif';
            $statusWarna = $isAktif ? 'sukses' : 'netral';
        }

        $bisaToggle = true;
        $alasanMati = '';
        if ($r instanceof \App\Models\Pengguna && $r->id === auth()->id()) {
            $bisaToggle = false;
            $alasanMati = 'Tidak dapat menonaktifkan akun sendiri.';
        }
    @endphp

    <form method="POST" action="{{ route($prefix.'.toggle-status', $r) }}" class="form-toggle-status" style="display: inline-block;">
        @csrf
        @method('PATCH')
        <label class="saklar-toggle {{ ! $bisaToggle ? 'saklar-toggle--mati' : '' }}"
               title="{{ ! $bisaToggle ? $alasanMati : 'Klik untuk mengubah status ('.$statusLabel.')' }}">
            <input type="checkbox"
                   name="aktif"
                   class="saklar-toggle__input"
                   data-saklar-status
                   data-url="{{ route($prefix.'.toggle-status', $r) }}"
                   data-label-aktif="{{ $k['key'] === 'status_operasional' ? 'Aktif' : 'Aktif' }}"
                   data-label-nonaktif="{{ $k['key'] === 'status_operasional' ? 'Tidak beroperasi' : ($k['key'] === 'status_petak' || $k['key'] === 'status_tim' ? 'Tidak aktif' : 'Nonaktif') }}"
                   @checked($isAktif)
                   @disabled(! $bisaToggle)>
            <span class="saklar-toggle__track">
                <span class="saklar-toggle__thumb"></span>
            </span>
            <span class="saklar-toggle__label saklar-toggle__label--{{ $isAktif ? 'aktif' : ($statusWarna === 'peringatan' ? 'peringatan' : 'nonaktif') }}">
                {{ $statusLabel }}
            </span>
        </label>
        <noscript>
            <button type="submit" class="tombol tombol--garis tombol--kecil" style="margin-left: 6px;">Ubah</button>
        </noscript>
    </form>
@else
    @switch($tipe)
        @case('status_drone')
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                @if ($nilai === \App\Models\Drone::STATUS_AKTIF)
                    <span class="lencana lencana--sukses" style="display: inline-flex; align-items: center; gap: 5px;">
                        <i class="bi bi-check-circle-fill"></i> Aktif
                    </span>
                @elseif ($nilai === \App\Models\Drone::STATUS_PERAWATAN)
                    <span class="lencana lencana--peringatan" style="display: inline-flex; align-items: center; gap: 5px;">
                        <i class="bi bi-tools"></i> Perawatan
                    </span>
                @else
                    <span class="lencana lencana--bahaya" style="display: inline-flex; align-items: center; gap: 5px;">
                        <i class="bi bi-x-circle-fill"></i> Tidak beroperasi
                    </span>
                @endif

                <button type="button"
                        class="tombol tombol--garis tombol--kecil tombol-modal-status"
                        data-drone-id="{{ $r->id }}"
                        data-drone-nomor="{{ $r->nomor_drone }}"
                        data-drone-nama="{{ $r->nama_drone }}"
                        data-status="{{ $nilai }}"
                        data-catatan="{{ e($r->catatan_perawatan ?? '') }}"
                        data-action="{{ route('master.drone.update-status', $r) }}"
                        title="Ubah status operasional & catatan perawatan">
                    <i class="bi bi-pencil-square"></i> Status
                </button>
            </div>
            @break

        @case('lokasi_drone')
            @if ($r->status_operasional === \App\Models\Drone::STATUS_PERAWATAN)
                <span class="lencana lencana--peringatan" style="display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bi bi-wrench"></i> Workshop / Bengkel
                </span>
            @elseif ($r->status_operasional === \App\Models\Drone::STATUS_TIDAK_BEROPERASI)
                <span class="lencana lencana--bahaya" style="display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bi bi-archive"></i> Gudang Nonaktif
                </span>
            @elseif ($r->timAktif && $r->timAktif->distrik)
                <div>
                    <span class="lencana lencana--sukses" style="display: inline-flex; align-items: center; gap: 4px;">
                        <i class="bi bi-geo-alt-fill"></i> On Duty: {{ $r->timAktif->distrik->nama_distrik }}
                    </span>
                    @if ($r->timAktif->kepalaTim)
                        <div class="teks-kecil teks-pudar" style="margin-top: 3px;">
                            <i class="bi bi-person"></i> Pilot: {{ $r->timAktif->kepalaTim->nama_lengkap }}
                        </div>
                    @endif
                </div>
            @else
                <span class="lencana lencana--netral" style="display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bi bi-box-seam"></i> Standby (Gudang)
                </span>
            @endif
            @break

        @case('catatan_perawatan')
            @if (!empty($nilai))
                @php
                    $isBahaya = ($r->status_operasional === \App\Models\Drone::STATUS_TIDAK_BEROPERASI);
                    $bgWarna = $isBahaya ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)';
                    $borderWarna = $isBahaya ? '#ef4444' : '#f59e0b';
                    $teksWarna = $isBahaya ? '#991b1b' : '#92400e';
                    $ikonWarna = $isBahaya ? 'bi-exclamation-octagon-fill' : 'bi-tools';
                @endphp
                <div style="background: {{ $bgWarna }}; border-left: 3px solid {{ $borderWarna }}; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; color: {{ $teksWarna }}; max-width: 280px; line-height: 1.4;">
                    <i class="bi {{ $ikonWarna }}" style="margin-right: 4px;"></i>{{ $nilai }}
                </div>
            @else
                <span class="teks-pudar">—</span>
            @endif
            @break

        @case('bool')
            <x-lencana :warna="$nilai ? 'success' : 'netral'">{{ $nilai ? 'Aktif' : 'Nonaktif' }}</x-lencana>
            @break

        @case('badge')
            @if ($kosong)
                <span class="teks-pudar">—</span>
            @else
                <x-lencana :warna="($k['badge'] ?? [])[$nilai] ?? 'netral'">{{ ($k['opsi'] ?? [])[$nilai] ?? $nilai }}</x-lencana>
            @endif
            @break

        @case('tanggal')
            {{ $kosong ? '—' : \Carbon\Carbon::parse($nilai)->translatedFormat('d M Y') }}
            @break

        @case('desimal')
            {{ $kosong ? '—' : number_format((float) $nilai, 2, ',', '.') }}
            @break

        @case('rupiah')
            {{ $kosong ? '—' : 'Rp '.number_format((float) $nilai, 2, ',', '.') }}
            @break

        @case('rupiah_tarif')
            @if ($r->tarifAktif)
                <span class="lencana lencana--sukses" style="font-size: 0.85rem; font-weight: 700; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bi bi-tag-fill"></i> Rp {{ number_format((float) $r->tarifAktif->nilai_tarif, 0, ',', '.') }} <span style="font-weight: 400; opacity: 0.85; font-size: 0.75rem;">/ Ha</span>
                </span>
            @else
                <span class="lencana lencana--peringatan" style="font-size: 0.8rem;">
                    Belum diatur
                </span>
            @endif
            @break

        @default
            @if ($kosong)
                <span class="teks-pudar">—</span>
            @else
                {{ \Illuminate\Support\Str::limit($nilai, 70) }}
            @endif
    @endswitch
@endif
