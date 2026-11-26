{{-- Form tambah/ubah data master (bersama untuk semua entitas). Field dirender oleh <x-field>. --}}
@extends('layouts.app')

@php($aksiJudul = ($record ? 'Ubah ' : 'Tambah ').$judul)

@section('judul', $aksiJudul)

@section('konten')
    <x-halaman-kepala :judul="$aksiJudul" :ikon="$ikon">
        <a href="{{ route($prefix.'.index') }}" class="tombol tombol--garis"><i class="bi bi-arrow-left"></i> Kembali</a>
    </x-halaman-kepala>

    @if ($prefix === 'master.tarif-pekerjaan')
        @if (! $record)
            <div class="kartu" style="margin-bottom: 1.25rem; background: #f0fdf4; border-left: 4px solid #10b981; padding: 1rem 1.25rem; border-radius: 8px;">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #dcfce7; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 700; color: #166534;">Alur Pembaruan Tarif Otomatis (Auto-Archive)</h4>
                        <p style="margin: 0; font-size: 0.85rem; color: #15803d; line-height: 1.45;">
                            Bila jenis pekerjaan yang dipilih telah memiliki tarif aktif, sistem akan <strong>otomatis menutup dan mengarsipkan tarif sebelumnya</strong> (masa berlaku diakhiri H-1 dari tanggal mulai baru) dan memindahkannya ke <strong>Tabel Riwayat</strong>. Riwayat SPK dan dokumen operasional terdahulu tetap akurat.
                        </p>
                    </div>
                </div>
            </div>
        @else
            @if (!empty($terkunciSpk))
                <div class="kartu" style="margin-bottom: 1.25rem; background: #fffbeb; border-left: 4px solid #f59e0b; padding: 1rem 1.25rem; border-radius: 8px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 700; color: #92400e;">Integritas Keuangan: Tarif Terkunci SPK</h4>
                            <p style="margin: 0; font-size: 0.85rem; color: #b45309; line-height: 1.45;">
                                Tarif ini telah digunakan pada dokumen SPK operasional. Nilai nominal tarif dan jenis pekerjaan <strong>dikunci</strong> demi keabsahan laporan keuangan. Jika harga berubah, silakan kembali ke daftar dan gunakan tombol <strong>"Ganti Tarif"</strong> untuk menetapkan harga baru mulai tanggal yang diinginkan.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif

    <form method="POST" action="{{ $record ? route($prefix.'.update', $record) : route($prefix.'.store') }}">
        @csrf
        @if ($record)
            @method('PUT')  

            
        @endif

        <div class="kartu">
            <div class="kartu__badan">
                <div class="form-grid">
                    @foreach ($field as $f)
                        <x-field :field="$f" :record="$record" />
                    @endforeach
                </div>

                <div class="form-aksi">
                    <a href="{{ route($prefix.'.index') }}" class="tombol tombol--garis">Batal</a>
                    <button type="submit" class="tombol tombol--utama" data-cegah-ganda>
                        <i class="bi bi-check2-circle"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
