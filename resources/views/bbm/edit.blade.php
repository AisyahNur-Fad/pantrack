@extends('layouts.app')

@section('judul', 'Ubah Transaksi BBM: ' . $bbm->nomor_transaksi)

@section('konten')
    <x-halaman-kepala
        :judul="'Ubah Transaksi: ' . $bbm->nomor_transaksi"
        deskripsi="Perbaiki rincian transaksi atau lengkapi bukti nota sebelum diajukan ulang ke Admin."
        ikon="bi-pencil-square">
        <a href="{{ route('bbm.show', $bbm) }}" class="tombol tombol--garis">
            <i class="bi bi-arrow-left"></i> Kembali ke Detail
        </a>
    </x-halaman-kepala>

    @if ($bbm->status_verifikasi === 'perlu_perbaikan' && $bbm->catatan_pemeriksaan)
        <div class="alert alert--bahaya mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Catatan Perbaikan dari Admin:</strong>
                <p class="mb-0">{{ $bbm->catatan_pemeriksaan }}</p>
            </div>
        </div>
    @endif

    <div class="kartu">
        <form method="POST" action="{{ route('bbm.update', $bbm) }}" enctype="multipart/form-data" class="alur-vertikal">
            @csrf
            @method('PUT')

            @include('bbm.partials.form-transaksi', ['bbm' => $bbm])

            <div class="bilah-aksi">
                <button type="submit" class="tombol tombol--utama">
                    <i class="bi bi-check2-circle"></i> Simpan & Ajukan Ulang
                </button>
                <a href="{{ route('bbm.show', $bbm) }}" class="tombol tombol--garis">Batal</a>
            </div>
        </form>
    </div>
@endsection
