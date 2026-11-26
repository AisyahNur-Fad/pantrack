{{-- Daftar data master (bersama untuk semua entitas). Variabel disiapkan MasterDataController::index(). --}}
@extends('layouts.app')

@section('judul', $judul)

@section('konten')
    <x-halaman-kepala :judul="$judul" :deskripsi="$deskripsi" :ikon="$ikon">
        <a href="{{ route($prefix.'.create') }}" class="tombol tombol--utama">
            <i class="bi bi-plus-lg"></i> Tambah {{ $judul }}
        </a>
    </x-halaman-kepala>

    @if ($prefix === 'master.tim' || $prefix === 'master.anggota-tim')
        <div class="tab-subnav-kembar" style="display: flex; gap: 8px; margin-bottom: 1.25rem; border-bottom: 2px solid rgba(0,0,0,0.06); padding-bottom: 0.75rem;">
            <a href="{{ route('master.tim.index') }}" 
               class="tombol {{ $prefix === 'master.tim' ? 'tombol--utama' : 'tombol--garis' }}" 
               style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                <i class="bi bi-people"></i> 1. Tim Operasional
            </a>
            <a href="{{ route('master.anggota-tim.index') }}" 
               class="tombol {{ $prefix === 'master.anggota-tim' ? 'tombol--utama' : 'tombol--garis' }}" 
               style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                <i class="bi bi-person-badge"></i> 2. Anggota Tim (Pilot/Co-Pilot)
            </a>
        </div>
    @elseif ($prefix === 'master.jenis-pekerjaan' || $prefix === 'master.tarif-pekerjaan')
        <div class="tab-subnav-kembar" style="display: flex; gap: 8px; margin-bottom: 1.25rem; border-bottom: 2px solid rgba(0,0,0,0.06); padding-bottom: 0.75rem;">
            <a href="{{ route('master.jenis-pekerjaan.index') }}" 
               class="tombol {{ $prefix === 'master.jenis-pekerjaan' ? 'tombol--utama' : 'tombol--garis' }}" 
               style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                <i class="bi bi-tools"></i> 1. Katalog Jenis Pekerjaan
            </a>
            <a href="{{ route('master.tarif-pekerjaan.index') }}" 
               class="tombol {{ $prefix === 'master.tarif-pekerjaan' ? 'tombol--utama' : 'tombol--garis' }}" 
               style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                <i class="bi bi-cash-coin"></i> 2. Standar & Riwayat Tarif
            </a>
        </div>
    @endif

    @if ($prefix === 'master.drone' && isset($ringkasanArmada))
        <div class="grid-kartu" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <a href="{{ route('master.drone.index') }}" class="kartu kartu-metrik" style="border-left: 4px solid #3b82f6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-cpu"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">Total Armada</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2;">{{ $ringkasanArmada['total'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Unit</span></div>
                </div>
            </a>

            <a href="{{ route('master.drone.index', ['status_operasional' => 'aktif']) }}" class="kartu kartu-metrik {{ request('status_operasional') === 'aktif' ? 'kartu-metrik--aktif' : '' }}" style="border-left: 4px solid #10b981;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🟢 Siap Kerja (Aktif)</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #059669;">{{ $ringkasanArmada['aktif'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Unit</span></div>
                </div>
            </a>

            <a href="{{ route('master.drone.index', ['status_operasional' => 'perawatan']) }}" class="kartu kartu-metrik {{ request('status_operasional') === 'perawatan' ? 'kartu-metrik--aktif' : '' }}" style="border-left: 4px solid #f59e0b;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #fffbeb; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-tools"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🟡 Dalam Perawatan</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #d97706;">{{ $ringkasanArmada['perawatan'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Unit</span></div>
                </div>
            </a>

            <a href="{{ route('master.drone.index', ['status_operasional' => 'tidak_beroperasi']) }}" class="kartu kartu-metrik {{ request('status_operasional') === 'tidak_beroperasi' ? 'kartu-metrik--aktif' : '' }}" style="border-left: 4px solid #ef4444;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🔴 Tidak Beroperasi</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #dc2626;">{{ $ringkasanArmada['tidak_beroperasi'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Unit</span></div>
                </div>
            </a>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #06b6d4;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfeff; color: #0891b2; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">📍 On Duty Lapangan</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #0891b2;">{{ $ringkasanArmada['on_duty'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Unit</span></div>
                </div>
            </div>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #8b5cf6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">📦 Standby (Pool)</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #7c3aed;">{{ $ringkasanArmada['standby'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Unit</span></div>
                </div>
            </div>
        </div>
    @endif

    @if ($prefix === 'master.tim' && isset($ringkasanTim))
        <div class="grid-kartu" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <a href="{{ route('master.tim.index') }}" class="kartu kartu-metrik" style="border-left: 4px solid #3b82f6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">Total Tim</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2;">{{ $ringkasanTim['total'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Tim</span></div>
                </div>
            </a>

            <a href="{{ route('master.tim.index', ['status_tim' => 'aktif']) }}" class="kartu kartu-metrik {{ request('status_tim') === 'aktif' ? 'kartu-metrik--aktif' : '' }}" style="border-left: 4px solid #10b981;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🟢 Tim Aktif</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #059669;">{{ $ringkasanTim['aktif'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Tim</span></div>
                </div>
            </a>

            <a href="{{ route('master.tim.index', ['status_tim' => 'tidak_aktif']) }}" class="kartu kartu-metrik {{ request('status_tim') === 'tidak_aktif' ? 'kartu-metrik--aktif' : '' }}" style="border-left: 4px solid #64748b;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-pause-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">⚪ Tim Nonaktif</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #475569;">{{ $ringkasanTim['tidak_aktif'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Tim</span></div>
                </div>
            </a>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #06b6d4;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfeff; color: #0891b2; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-cpu"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🛸 Drone Bertugas</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #0891b2;">{{ $ringkasanTim['drone_terhubung'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Unit</span></div>
                </div>
            </div>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #8b5cf6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🪪 Pilot Ditugaskan</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #7c3aed;">{{ $ringkasanTim['pilot_bertugas'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Orang</span></div>
                </div>
            </div>
        </div>
    @endif

    @if ($prefix === 'master.petak' && isset($ringkasanPetak))
        <div class="grid-kartu" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <a href="{{ route('master.petak.index') }}" class="kartu kartu-metrik" style="border-left: 4px solid #10b981;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">Total Petak</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2;">{{ $ringkasanPetak['total'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Petak</span></div>
                </div>
            </a>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #3b82f6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-bounding-box-circles"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">📐 Luas Lahan Total</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #2563eb;">{{ number_format($ringkasanPetak['total_luas'], 1, ',', '.') }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Ha</span></div>
                </div>
            </div>

            <a href="{{ route('master.petak.index', ['status_petak' => 'aktif']) }}" class="kartu kartu-metrik {{ request('status_petak') === 'aktif' ? 'kartu-metrik--aktif' : '' }}" style="border-left: 4px solid #059669;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🟢 Petak Aktif</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #059669;">{{ $ringkasanPetak['aktif'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Petak</span></div>
                </div>
            </a>

            <a href="{{ route('master.petak.index', ['status_petak' => 'tidak_aktif']) }}" class="kartu kartu-metrik {{ request('status_petak') === 'tidak_aktif' ? 'kartu-metrik--aktif' : '' }}" style="border-left: 4px solid #64748b;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-pause-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">⚪ Petak Nonaktif</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #475569;">{{ $ringkasanPetak['tidak_aktif'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Petak</span></div>
                </div>
            </a>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #f59e0b;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #fffbeb; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">📍 Sebaran Distrik</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #d97706;">{{ $ringkasanPetak['total_distrik'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Distrik</span></div>
                </div>
            </div>
        </div>
    @endif

    @if ($prefix === 'master.jenis-pekerjaan' && isset($ringkasanJenis))
        <div class="grid-kartu" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <a href="{{ route('master.jenis-pekerjaan.index') }}" class="kartu kartu-metrik" style="border-left: 4px solid #3b82f6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-collection-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">Total Katalog</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2;">{{ $ringkasanJenis['total'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Layanan</span></div>
                </div>
            </a>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #10b981;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-bullseye"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🌾 Pekerjaan Pokok</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #059669;">{{ $ringkasanJenis['total_utama'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Item</span></div>
                </div>
            </div>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #0284c7;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🗺️ Layanan Add-on</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #0284c7;">{{ $ringkasanJenis['total_addon'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Item</span></div>
                </div>
            </div>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #8b5cf6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🟢 Layanan Aktif</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #7c3aed;">{{ $ringkasanJenis['total_aktif'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Item</span></div>
                </div>
            </div>
        </div>
    @endif

    @if ($prefix === 'master.tarif-pekerjaan' && isset($ringkasanTarif))
        <div class="grid-kartu" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <a href="{{ route('master.tarif-pekerjaan.index') }}" class="kartu kartu-metrik" style="border-left: 4px solid #3b82f6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">Total Record Tarif</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2;">{{ $ringkasanTarif['total'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Tarif</span></div>
                </div>
            </a>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #10b981;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">🟢 Tarif Aktif Saat Ini</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #059669;">{{ $ringkasanTarif['total_aktif'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Standar</span></div>
                </div>
            </div>

            <div class="kartu kartu-metrik" style="border-left: 4px solid #64748b;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">📜 Riwayat & Arsip Tarif</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #475569;">{{ $ringkasanTarif['total_riwayat'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Catatan</span></div>
                </div>
            </div>

            <a href="{{ route('master.jenis-pekerjaan.index') }}" class="kartu kartu-metrik" style="border-left: 4px solid #8b5cf6;">
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="bi bi-collection-fill"></i>
                </div>
                <div>
                    <div class="teks-pudar teks-kecil" style="font-size: 0.8rem;">📋 Layanan Terdaftar</div>
                    <div style="font-size: 1.35rem; font-weight: 700; line-height: 1.2; color: #7c3aed;">{{ $ringkasanTarif['total_jenis'] }} <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Layanan</span></div>
                </div>
            </a>
        </div>
    @endif

    <div class="kartu" style="margin-bottom: 1.25rem;">
        @if ($bisaCari || count($filter))
            <form method="GET" class="bilah-alat">
                @if ($bisaCari)
                    <div class="cari-bungkus">
                        <i class="bi bi-search"></i>
                        <input type="search" name="cari" value="{{ $cari }}" class="masukan" placeholder="Cari {{ mb_strtolower($judul) }}…">
                    </div>
                @endif

                @foreach ($filter as $f)
                    <select name="{{ $f['name'] }}" class="masukan" data-kirim-otomatis aria-label="{{ $f['label'] }}">
                        <option value="">{{ $f['label'] }}</option>
                        @foreach ($f['options'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected((string) request($f['name']) === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endforeach

                <button type="submit" class="tombol tombol--garis"><i class="bi bi-funnel"></i> Terapkan</button>

                @if ($cari !== '' || collect($filter)->contains(fn ($f) => request()->filled($f['name'])))
                    <a href="{{ route($prefix.'.index') }}" class="tombol tombol--garis"><i class="bi bi-x-lg"></i> Reset</a>
                @endif
            </form>
        @endif
    </div>

    @if ($prefix === 'master.jenis-pekerjaan' && isset($pekerjaanUtama) && isset($pekerjaanAddon))
        {{-- TABEL 1: PEKERJAAN POKOK / UTAMA --}}
        <div class="kartu" style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 36px; height: 36px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        <i class="bi bi-bullseye"></i>
                    </span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;">1. Pekerjaan Pokok / Utama (Aplikasi Drone Lapangan)</h3>
                        <p class="teks-kecil teks-pudar" style="margin: 2px 0 0 0;">Jenis pekerjaan pokok yang otomatis muncul pada pilihan dropdown formulir SPK.</p>
                    </div>
                </div>
                <span class="lencana lencana--sukses" style="font-weight: 600; font-size: 0.825rem; padding: 4px 10px;">
                    <i class="bi bi-check-circle-fill"></i> {{ count($pekerjaanUtama) }} Pekerjaan Utama
                </span>
            </div>

            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th style="width: 48px;">No</th>
                            <th>Kode</th>
                            <th>Nama Jenis Pekerjaan</th>
                            <th>Kategori</th>
                            <th>Tarif Aktif Saat Ini</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pekerjaanUtama as $r)
                            <tr>
                                <td class="teks-pudar">{{ $loop->iteration }}</td>
                                <td><code style="font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">{{ $r->kode_pekerjaan }}</code></td>
                                <td style="font-weight: 600; color: #1e293b;">{{ $r->nama_pekerjaan }}</td>
                                <td>
                                    <span class="lencana lencana--sukses" style="font-size: 0.78rem;">
                                        <i class="bi bi-check-circle"></i> Utama
                                    </span>
                                </td>
                                <td>
                                    @if ($r->tarifAktif)
                                        <span class="lencana lencana--sukses" style="font-size: 0.85rem; font-weight: 700; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="bi bi-tag-fill"></i> Rp {{ number_format((float) $r->tarifAktif->nilai_tarif, 0, ',', '.') }} <span style="font-weight: 400; opacity: 0.85; font-size: 0.75rem;">/ Ha</span>
                                        </span>
                                    @else
                                        <span class="lencana lencana--peringatan" style="font-size: 0.8rem;">Belum diatur</span>
                                    @endif
                                </td>
                                <td class="teks-pudar teks-kecil">{{ $r->keterangan ?: '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('master.jenis-pekerjaan.toggle-status', $r) }}" class="form-toggle-status" style="display: inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <label class="saklar-toggle" title="Klik untuk mengubah status ({{ $r->aktif ? 'Aktif' : 'Nonaktif' }})">
                                            <input type="checkbox" name="aktif" class="saklar-toggle__input" data-saklar-status data-url="{{ route('master.jenis-pekerjaan.toggle-status', $r) }}" @checked($r->aktif)>
                                            <span class="saklar-toggle__track"><span class="saklar-toggle__thumb"></span></span>
                                            <span class="saklar-toggle__label saklar-toggle__label--{{ $r->aktif ? 'aktif' : 'nonaktif' }}">{{ $r->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                        </label>
                                    </form>
                                </td>
                                <td class="aksi">
                                    <a href="{{ route('master.tarif-pekerjaan.index', ['jenis_pekerjaan_id' => $r->id]) }}" class="tombol tombol--garis tombol--kecil" title="Lihat Riwayat Tarif">
                                        <i class="bi bi-cash-coin"></i> Tarif
                                    </a>
                                    <a href="{{ route('master.jenis-pekerjaan.edit', $r) }}" class="tombol tombol--garis tombol--kecil" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i> Ubah
                                    </a>
                                    @if ($bolehHapus)
                                        <form method="POST" action="{{ route('master.jenis-pekerjaan.destroy', $r) }}"
                                              data-konfirmasi="{{ 'Hapus jenis pekerjaan "'.$r->nama_pekerjaan.'"? Data yang dihapus tidak dapat dikembalikan.' }}"
                                              data-konfirmasi-judul="Hapus jenis pekerjaan?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil tombol--ikon" title="Hapus" aria-label="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="teks-tengah teks-pudar" style="padding: 1.5rem;">Belum ada pekerjaan pokok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TABEL 2: LAYANAN / BIAYA TAMBAHAN (ADD-ON) --}}
        <div class="kartu" style="margin-bottom: 1.5rem; border-top: 3px solid #0284c7;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 36px; height: 36px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        <i class="bi bi-geo-alt-fill"></i>
                    </span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;">2. Layanan & Biaya Tambahan (Add-on / Opsi Lapangan)</h3>
                        <p class="teks-kecil teks-pudar" style="margin: 2px 0 0 0;">Jasa pendukung operasional yang disertakan sebagai opsi tambahan (checkbox) pada formulir SPK.</p>
                    </div>
                </div>
                <span class="lencana lencana--info" style="font-weight: 600; font-size: 0.825rem; padding: 4px 10px;">
                    <i class="bi bi-plus-circle-fill"></i> {{ count($pekerjaanAddon) }} Layanan Add-on
                </span>
            </div>

            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th style="width: 48px;">No</th>
                            <th>Kode</th>
                            <th>Nama Layanan Tambahan</th>
                            <th>Kategori</th>
                            <th>Tarif Aktif Saat Ini</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pekerjaanAddon as $r)
                            <tr>
                                <td class="teks-pudar">{{ $loop->iteration }}</td>
                                <td><code style="font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 2px 6px; border-radius: 4px;">{{ $r->kode_pekerjaan }}</code></td>
                                <td style="font-weight: 600; color: #1e293b;">{{ $r->nama_pekerjaan }}</td>
                                <td>
                                    <span class="lencana lencana--info" style="font-size: 0.78rem;">
                                        <i class="bi bi-plus-circle"></i> Jasa Add-on
                                    </span>
                                </td>
                                <td>
                                    @if ($r->tarifAktif)
                                        <span class="lencana lencana--sukses" style="font-size: 0.85rem; font-weight: 700; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="bi bi-tag-fill"></i> Rp {{ number_format((float) $r->tarifAktif->nilai_tarif, 0, ',', '.') }} <span style="font-weight: 400; opacity: 0.85; font-size: 0.75rem;">/ Ha</span>
                                        </span>
                                    @else
                                        <span class="lencana lencana--peringatan" style="font-size: 0.8rem;">Belum diatur</span>
                                    @endif
                                </td>
                                <td class="teks-pudar teks-kecil">{{ $r->keterangan ?: '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('master.jenis-pekerjaan.toggle-status', $r) }}" class="form-toggle-status" style="display: inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <label class="saklar-toggle" title="Klik untuk mengubah status ({{ $r->aktif ? 'Aktif' : 'Nonaktif' }})">
                                            <input type="checkbox" name="aktif" class="saklar-toggle__input" data-saklar-status data-url="{{ route('master.jenis-pekerjaan.toggle-status', $r) }}" @checked($r->aktif)>
                                            <span class="saklar-toggle__track"><span class="saklar-toggle__thumb"></span></span>
                                            <span class="saklar-toggle__label saklar-toggle__label--{{ $r->aktif ? 'aktif' : 'nonaktif' }}">{{ $r->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                        </label>
                                    </form>
                                </td>
                                <td class="aksi">
                                    <a href="{{ route('master.tarif-pekerjaan.index', ['jenis_pekerjaan_id' => $r->id]) }}" class="tombol tombol--garis tombol--kecil" title="Lihat Riwayat Tarif">
                                        <i class="bi bi-cash-coin"></i> Tarif
                                    </a>
                                    <a href="{{ route('master.jenis-pekerjaan.edit', $r) }}" class="tombol tombol--garis tombol--kecil" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i> Ubah
                                    </a>
                                    @if ($bolehHapus)
                                        <form method="POST" action="{{ route('master.jenis-pekerjaan.destroy', $r) }}"
                                              data-konfirmasi="{{ 'Hapus layanan "'.$r->nama_pekerjaan.'"? Data yang dihapus tidak dapat dikembalikan.' }}"
                                              data-konfirmasi-judul="Hapus layanan?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil tombol--ikon" title="Hapus" aria-label="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="teks-tengah teks-pudar" style="padding: 1.5rem;">Belum ada layanan tambahan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($prefix === 'master.tarif-pekerjaan' && isset($tarifAktif) && isset($riwayatTarif))
        {{-- TABEL 1: TARIF BERLAKU SAAT INI (STANDAR AKTIF) --}}
        <div class="kartu" style="margin-bottom: 1.5rem; border-top: 3px solid #10b981;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 36px; height: 36px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        <i class="bi bi-cash-stack"></i>
                    </span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;">1. Tarif Berlaku Saat Ini (Standar Aktif)</h3>
                        <p class="teks-kecil teks-pudar" style="margin: 2px 0 0 0;">Daftar tarif resmi per hektar yang sedang berlaku aktif dan digunakan otomatis pada penerbitan SPK baru.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="lencana lencana--sukses" style="font-weight: 600; font-size: 0.825rem; padding: 4px 10px;">
                        <i class="bi bi-check-circle-fill"></i> {{ count($tarifAktif) }} Tarif Aktif
                    </span>
                    <a href="{{ route('master.tarif-pekerjaan.create') }}" class="tombol tombol--utama tombol--kecil">
                        <i class="bi bi-plus-lg"></i> Tambah / Ganti Tarif
                    </a>
                </div>
            </div>

            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th style="width: 48px;">No</th>
                            <th>Jenis Pekerjaan</th>
                            <th>Kategori</th>
                            <th class="angka">Tarif Standar (Rp / Ha)</th>
                            <th>Masa Berlaku</th>
                            <th>Keterangan / Dasar</th>
                            <th>Status</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tarifAktif as $r)
                            <tr>
                                <td class="teks-pudar">{{ $loop->iteration }}</td>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 2px;">
                                        {{ $r->jenisPekerjaan?->nama_pekerjaan ?? '—' }}
                                    </div>
                                    <code style="font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 0.78rem;">
                                        {{ $r->jenisPekerjaan?->kode_pekerjaan ?? '—' }}
                                    </code>
                                </td>
                                <td>
                                    @if ($r->jenisPekerjaan?->is_addon)
                                        <span class="lencana lencana--info" style="font-size: 0.75rem;">
                                            <i class="bi bi-plus-circle"></i> Add-on
                                        </span>
                                    @else
                                        <span class="lencana lencana--sukses" style="font-size: 0.75rem;">
                                            <i class="bi bi-check-circle"></i> Utama
                                        </span>
                                    @endif
                                </td>
                                <td class="angka">
                                    <span style="font-size: 1rem; font-weight: 700; color: #059669; background: #ecfdf5; padding: 3px 8px; border-radius: 6px; display: inline-block;">
                                        Rp {{ number_format((float) $r->nilai_tarif, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; font-weight: 500; color: #334155;">
                                        Mulai: {{ \Carbon\Carbon::parse($r->berlaku_mulai)->isoFormat('D MMM Y') }}
                                    </div>
                                    <div class="teks-kecil teks-pudar">
                                        Sampai: {{ $r->berlaku_sampai ? \Carbon\Carbon::parse($r->berlaku_sampai)->isoFormat('D MMM Y') : 'Seterusnya (Aktif)' }}
                                    </div>
                                </td>
                                <td class="teks-pudar teks-kecil" style="max-width: 250px;">
                                    {{ $r->keterangan ?: '—' }}
                                </td>
                                <td>
                                    <span class="lencana lencana--sukses" style="font-size: 0.78rem;">
                                        <i class="bi bi-check-circle-fill"></i> Aktif Berlaku
                                    </span>
                                </td>
                                <td class="aksi">
                                    <a href="{{ route('master.tarif-pekerjaan.create', ['ganti_jenis_id' => $r->jenis_pekerjaan_id]) }}" class="tombol tombol--utama tombol--kecil" title="Tetapkan Tarif Baru (Tarif lama otomatis diarsipkan)">
                                        <i class="bi bi-arrow-repeat"></i> Ganti Tarif
                                    </a>
                                    <a href="{{ route('master.tarif-pekerjaan.edit', $r) }}" class="tombol tombol--garis tombol--kecil" title="Koreksi Data Tarif">
                                        <i class="bi bi-pencil-square"></i> Koreksi
                                    </a>
                                    @if ($bolehHapus)
                                        <form method="POST" action="{{ route('master.tarif-pekerjaan.destroy', $r) }}"
                                              data-konfirmasi="{{ 'Hapus tarif '.$r->jenisPekerjaan?->nama_pekerjaan.' (Rp '.number_format((float)$r->nilai_tarif, 0, ',', '.').')? Data yang dihapus tidak dapat dikembalikan.' }}"
                                              data-konfirmasi-judul="Hapus tarif pekerjaan?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil tombol--ikon" title="Hapus" aria-label="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="teks-tengah teks-pudar" style="padding: 1.5rem;">Tidak ada data tarif yang sedang aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TABEL 2: RIWAYAT & ARSIP PERUBAHAN TARIF (SEBELUM DIUBAH) --}}
        <div class="kartu" style="margin-bottom: 1.5rem; border-top: 3px solid #64748b;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        <i class="bi bi-clock-history"></i>
                    </span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;">2. Riwayat & Arsip Perubahan Tarif (Sebelum Diubah)</h3>
                        <p class="teks-kecil teks-pudar" style="margin: 2px 0 0 0;">Catatan nilai tarif sebelum dilakukan perubahan/pembaruan. Disimpan otomatis sebagai riwayat audit historis SPK.</p>
                    </div>
                </div>
                <span class="lencana" style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.825rem; padding: 4px 10px;">
                    <i class="bi bi-archive-fill"></i> {{ count($riwayatTarif) }} Riwayat Tarif
                </span>
            </div>

            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th style="width: 48px;">No</th>
                            <th>Jenis Pekerjaan</th>
                            <th>Kategori</th>
                            <th class="angka">Tarif Lama (Rp / Ha)</th>
                            <th>Periode Riwayat Berlaku</th>
                            <th>Catatan Perubahan</th>
                            <th>Dicatat Oleh</th>
                            <th>Status</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatTarif as $r)
                            <tr>
                                <td class="teks-pudar">{{ $loop->iteration }}</td>
                                <td>
                                    <div style="font-weight: 600; color: #334155; margin-bottom: 2px;">
                                        {{ $r->jenisPekerjaan?->nama_pekerjaan ?? '—' }}
                                    </div>
                                    <code style="color: #64748b; background: #f8fafc; padding: 1px 6px; border-radius: 4px; font-size: 0.78rem;">
                                        {{ $r->jenisPekerjaan?->kode_pekerjaan ?? '—' }}
                                    </code>
                                </td>
                                <td>
                                    @if ($r->jenisPekerjaan?->is_addon)
                                        <span class="lencana" style="background: #f1f5f9; color: #64748b; font-size: 0.75rem;">
                                            <i class="bi bi-plus-circle"></i> Add-on
                                        </span>
                                    @else
                                        <span class="lencana" style="background: #f1f5f9; color: #64748b; font-size: 0.75rem;">
                                            <i class="bi bi-check-circle"></i> Utama
                                        </span>
                                    @endif
                                </td>
                                <td class="angka">
                                    <span style="font-size: 0.95rem; font-weight: 600; color: #64748b; text-decoration: line-through; opacity: 0.85;">
                                        Rp {{ number_format((float) $r->nilai_tarif, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; color: #475569;">
                                        {{ \Carbon\Carbon::parse($r->berlaku_mulai)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($r->berlaku_sampai)->isoFormat('D MMM Y') }}
                                    </div>
                                    <div class="teks-kecil teks-pudar">
                                        Durasi: {{ \Carbon\Carbon::parse($r->berlaku_mulai)->diffInDays(\Carbon\Carbon::parse($r->berlaku_sampai)) + 1 }} hari
                                    </div>
                                </td>
                                <td class="teks-pudar teks-kecil" style="max-width: 250px;">
                                    {{ $r->keterangan ?: '—' }}
                                </td>
                                <td>
                                    <div style="font-size: 0.825rem; font-weight: 500; color: #334155;">
                                        {{ $r->pembuat?->nama ?? 'Sistem' }}
                                    </div>
                                    <div class="teks-kecil teks-pudar">
                                        {{ $r->created_at ? $r->created_at->isoFormat('D MMM Y HH:mm') : '—' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="lencana" style="background: #f1f5f9; color: #64748b; font-size: 0.78rem;">
                                        <i class="bi bi-archive-fill"></i> Arsip / Nonaktif
                                    </span>
                                </td>
                                <td class="aksi">
                                    <a href="{{ route('master.tarif-pekerjaan.edit', $r) }}" class="tombol tombol--garis tombol--kecil" title="Detail / Ubah Data Riwayat">
                                        <i class="bi bi-pencil-square"></i> Ubah
                                    </a>
                                    @if ($bolehHapus)
                                        <form method="POST" action="{{ route('master.tarif-pekerjaan.destroy', $r) }}"
                                              data-konfirmasi="{{ 'Hapus arsip tarif '.$r->jenisPekerjaan?->nama_pekerjaan.' (Rp '.number_format((float)$r->nilai_tarif, 0, ',', '.').')? Data yang dihapus tidak dapat dikembalikan.' }}"
                                              data-konfirmasi-judul="Hapus arsip tarif?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil tombol--ikon" title="Hapus" aria-label="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="teks-tengah teks-pudar" style="padding: 1.5rem;">Belum ada arsip riwayat perubahan tarif. Semua tarif saat ini masih dalam periode aktif perdana.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="kartu">
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>No</th>
                            @foreach ($kolom as $k)
                                <th class="{{ in_array($k['tipe'] ?? '', ['desimal', 'rupiah']) ? 'angka' : '' }}">{{ $k['label'] }}</th>
                            @endforeach
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as $r)
                            <tr>
                                <td class="teks-pudar">{{ $baris->firstItem() + $loop->index }}</td>
                                @foreach ($kolom as $k)
                                    <td class="{{ in_array($k['tipe'] ?? '', ['desimal', 'rupiah']) ? 'angka' : '' }}">
                                        @include('master.partials.sel', ['r' => $r, 'k' => $k])
                                    </td>
                                @endforeach
                                <td class="aksi">
                                    @if ($prefix === 'master.tim')
                                        <a href="{{ route('master.anggota-tim.index', ['tim_id' => $r->id]) }}" class="tombol tombol--garis tombol--kecil" title="Kelola Anggota Tim">
                                            <i class="bi bi-person-badge"></i> Anggota
                                        </a>
                                    @elseif ($prefix === 'master.jenis-pekerjaan')
                                        <a href="{{ route('master.tarif-pekerjaan.index', ['jenis_pekerjaan_id' => $r->id]) }}" class="tombol tombol--garis tombol--kecil" title="Lihat Riwayat Tarif">
                                            <i class="bi bi-cash-coin"></i> Tarif
                                        </a>
                                    @endif

                                    <a href="{{ route($prefix.'.edit', $r) }}" class="tombol tombol--garis tombol--kecil" title="Ubah">
                                        <i class="bi bi-pencil-square"></i> Ubah
                                    </a>

                                    @if ($bolehHapus)
                                        <form method="POST" action="{{ route($prefix.'.destroy', $r) }}"
                                              data-konfirmasi="{{ 'Hapus '.$judul.' "'.$labelBaris[$r->getKey()].'"? Data yang dihapus tidak dapat dikembalikan.' }}"
                                              data-konfirmasi-judul="Hapus data?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil tombol--ikon" title="Hapus" aria-label="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($kolom) + 2 }}">
                                    <x-kosong :ikon="$ikon" :judul="'Belum ada data '.mb_strtolower($judul)">
                                        @if ($cari !== '')
                                            Tidak ada hasil untuk pencarian "{{ $cari }}".
                                        @else
                                            Klik "Tambah {{ $judul }}" untuk menambahkan data pertama.
                                        @endif
                                    </x-kosong>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $baris->links() }}
        </div>
    @endif

    @if ($prefix === 'master.drone')
        {{-- Modal Dialog Ubah Status & Catatan Perawatan Drone --}}
        <dialog id="dialog-status-drone" class="dialog" style="max-width: 500px; width: 90%;">
            <form id="form-status-drone" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="dialog__badan" style="text-align: left; padding: 1.5rem;">
                    <div class="dialog__ikon" style="color: #d97706; background: #fef3c7; margin: 0 auto 1rem auto; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h2 class="dialog__judul" style="text-align: center; margin-bottom: 0.25rem; font-size: 1.25rem;">Ubah Status & Perawatan</h2>
                    <p class="dialog__pesan" style="text-align: center; font-size: 0.85rem; color: #64748b; margin-bottom: 1.25rem;">
                        Atur kondisi unit operasional dan berikan keterangan perawatan secara langsung.
                    </p>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                            <span class="teks-pudar">Nomor Drone:</span>
                            <strong id="modal-drone-nomor" style="font-family: monospace; font-size: 1.1rem; color: #1e293b;">-</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="teks-pudar">Nama Unit:</span>
                            <span id="modal-drone-nama" class="teks-tebal">-</span>
                        </div>
                    </div>

                    <div class="form-grup" style="margin-bottom: 1.25rem;">
                        <label class="form-label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Status Operasional Unit <span class="wajib">*</span></label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label id="label-status-aktif" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1.5px solid #e2e8f0; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                                <input type="radio" name="status_operasional" value="aktif">
                                <div>
                                    <span class="lencana lencana--sukses" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-check-circle-fill"></i> Aktif
                                    </span>
                                    <span class="teks-kecil teks-pudar" style="margin-left: 6px;">Unit sehat, prima & siap operasi</span>
                                </div>
                            </label>
                            <label id="label-status-perawatan" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1.5px solid #e2e8f0; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                                <input type="radio" name="status_operasional" value="perawatan">
                                <div>
                                    <span class="lencana lencana--peringatan" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-tools"></i> Perawatan
                                    </span>
                                    <span class="teks-kecil teks-pudar" style="margin-left: 6px;">Masuk bengkel / perbaikan berkala</span>
                                </div>
                            </label>
                            <label id="label-status-tidak-beroperasi" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1.5px solid #e2e8f0; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                                <input type="radio" name="status_operasional" value="tidak_beroperasi">
                                <div>
                                    <span class="lencana lencana--bahaya" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-x-circle-fill"></i> Tidak beroperasi
                                    </span>
                                    <span class="teks-kecil teks-pudar" style="margin-left: 6px;">Rusak berat / afkir / nonaktif gudang</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-grup" style="margin-bottom: 0.5rem;">
                        <label class="form-label" for="modal-drone-catatan" style="font-weight: 600; margin-bottom: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                            <span>Catatan Perawatan / Kendala Unit</span>
                            <span id="badge-catatan-status" class="teks-kecil"></span>
                        </label>
                        <textarea id="modal-drone-catatan" name="catatan_perawatan" rows="3" class="masukan" style="width: 100%; box-sizing: border-box; transition: all 0.2s;" placeholder="Contoh: Ganti 2 propeller & kalibrasi kompas di workshop..."></textarea>
                        <div id="modal-drone-petunjuk" class="teks-kecil" style="margin-top: 6px; line-height: 1.45; color: #64748b;">
                            Beri penjelasan apa yang sedang diperbaiki atau kendala unit agar kru lapangan paham.
                        </div>
                    </div>
                </div>

                <div class="dialog__aksi" style="display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="tombol tombol--garis" onclick="document.getElementById('dialog-status-drone').close()">Batal</button>
                    <button type="submit" class="tombol tombol--utama">
                        <i class="bi bi-check-lg"></i> Simpan Status
                    </button>
                </div>
            </form>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dlg = document.getElementById('dialog-status-drone');
                const form = document.getElementById('form-status-drone');
                if (!dlg || !form) return;

                const textarea = document.getElementById('modal-drone-catatan');
                const petunjuk = document.getElementById('modal-drone-petunjuk');
                const badgeStatus = document.getElementById('badge-catatan-status');
                const radios = form.querySelectorAll('input[name="status_operasional"]');
                const labelAktif = document.getElementById('label-status-aktif');
                const labelPerawatan = document.getElementById('label-status-perawatan');
                const labelTidakBeroperasi = document.getElementById('label-status-tidak-beroperasi');

                function updateUiForStatus(statusVal, isAutoSwitch) {
                    // Reset styling label
                    [labelAktif, labelPerawatan, labelTidakBeroperasi].forEach(lbl => {
                        if (lbl) {
                            lbl.style.borderColor = '#e2e8f0';
                            lbl.style.backgroundColor = '#ffffff';
                        }
                    });

                    if (statusVal === 'aktif') {
                        if (labelAktif) {
                            labelAktif.style.borderColor = '#22c55e';
                            labelAktif.style.backgroundColor = '#f0fdf4';
                        }
                        if (badgeStatus) {
                            badgeStatus.innerHTML = '<span style="color: #16a34a; font-weight: 600;"><i class="bi bi-shield-check"></i> Unit Siap Terbang</span>';
                        }
                        if (petunjuk) {
                            petunjuk.innerHTML = '<span style="color: #16a34a;"><i class="bi bi-info-circle-fill"></i> <strong>Unit Siap Operasi:</strong> Catatan kendala otomatis dibersihkan karena unit dinyatakan sehat dan lulus inspeksi.</span>';
                        }
                        textarea.placeholder = 'Unit prima tanpa kendala operasional...';
                        textarea.style.backgroundColor = '#f8fafc';
                        textarea.style.borderColor = '#cbd5e1';
                    } else if (statusVal === 'perawatan') {
                        if (labelPerawatan) {
                            labelPerawatan.style.borderColor = '#f59e0b';
                            labelPerawatan.style.backgroundColor = '#fffbeb';
                        }
                        if (badgeStatus) {
                            badgeStatus.innerHTML = '<span style="color: #d97706; font-weight: 600;"><i class="bi bi-tools"></i> Workshop / Servis</span>';
                        }
                        if (petunjuk) {
                            petunjuk.innerHTML = isAutoSwitch
                                ? '<span style="color: #b45309;"><i class="bi bi-lightning-charge-fill"></i> <strong>Status otomatis dialihkan ke Perawatan</strong> karena Anda mengisi catatan kendala. Unit akan dikunci dari penugasan lapangan.</span>'
                                : '<span style="color: #b45309;"><i class="bi bi-tools"></i> <strong>Jelaskan item perbaikan:</strong> Catat bagian yang diservis (oli, propeller, nozzle, kalibrasi sensor) agar kru lapangan paham.</span>';
                        }
                        textarea.placeholder = 'Contoh: Ganti oli genset, servis pompa nozzle, ganti propeller, kalibrasi di workshop...';
                        textarea.style.backgroundColor = '#ffffff';
                        textarea.style.borderColor = '#f59e0b';
                    } else if (statusVal === 'tidak_beroperasi') {
                        if (labelTidakBeroperasi) {
                            labelTidakBeroperasi.style.borderColor = '#ef4444';
                            labelTidakBeroperasi.style.backgroundColor = '#fef2f2';
                        }
                        if (badgeStatus) {
                            badgeStatus.innerHTML = '<span style="color: #dc2626; font-weight: 600;"><i class="bi bi-x-octagon-fill"></i> Nonaktif / Afkir</span>';
                        }
                        if (petunjuk) {
                            petunjuk.innerHTML = '<span style="color: #dc2626;"><i class="bi bi-exclamation-triangle-fill"></i> <strong>Wajib sertakan alasan:</strong> Tuliskan penyebab unit ditarik (crash berat, motor terbakar, menunggu suku cadang impor, atau afkir gudang).</span>';
                        }
                        textarea.placeholder = 'Contoh: Crash di petak SRA5150 arm kanan patah, motor terbakar, ditarik ke gudang logistik...';
                        textarea.style.backgroundColor = '#ffffff';
                        textarea.style.borderColor = '#ef4444';
                    }
                }

                // Tombol status pada tabel
                document.querySelectorAll('.tombol-modal-status').forEach(btn => {
                    btn.addEventListener('click', function () {
                        form.action = this.dataset.action;
                        document.getElementById('modal-drone-nomor').textContent = this.dataset.droneNomor;
                        document.getElementById('modal-drone-nama').textContent = this.dataset.droneNama;

                        const val = this.dataset.status || 'aktif';
                        const radio = form.querySelector(`input[name="status_operasional"][value="${val}"]`);
                        if (radio) radio.checked = true;

                        const catatan = this.dataset.catatan || '';
                        textarea.value = catatan;

                        updateUiForStatus(val, false);
                        dlg.showModal();
                    });
                });

                // Deteksi klik radio button
                radios.forEach(radio => {
                    radio.addEventListener('change', function () {
                        if (this.checked) {
                            if (this.value === 'aktif') {
                                // Saat beralih ke aktif, bersihkan catatan kendala lama secara otomatis
                                textarea.value = '';
                            }
                            updateUiForStatus(this.value, false);
                            if (this.value !== 'aktif' && textarea.value.trim() === '') {
                                textarea.focus();
                            }
                        }
                    });
                });

                // SMART AUTO-SWITCH: Jika status awal masih "aktif" dan pengguna mulai mengetik di textarea,
                // sistem otomatis memindahkan radio button ke "perawatan"!
                textarea.addEventListener('input', function () {
                    const checkedRadio = form.querySelector('input[name="status_operasional"]:checked');
                    if (checkedRadio && checkedRadio.value === 'aktif' && this.value.trim().length > 0) {
                        const radioPerawatan = form.querySelector('input[name="status_operasional"][value="perawatan"]');
                        if (radioPerawatan) {
                            radioPerawatan.checked = true;
                            updateUiForStatus('perawatan', true);
                        }
                    }
                });
            });
        </script>
    @endif
@endsection
