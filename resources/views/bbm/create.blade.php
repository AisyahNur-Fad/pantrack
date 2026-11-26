@extends('layouts.app')

@section('judul', 'Catat Transaksi BBM')

@section('konten')
    <x-halaman-kepala
        judul="Catat Transaksi BBM"
        deskripsi="Pencatatan pengisian stok atau pemakaian bahan bakar tim dengan nomor transaksi otomatis."
        ikon="bi-fuel-pump-fill">
        <a href="{{ route('bbm.index') }}" class="tombol tombol--garis">
            <i class="bi bi-arrow-left"></i> Kembali ke Monitoring BBM
        </a>
    </x-halaman-kepala>

    <div class="kartu">
        <form method="POST" action="{{ route('bbm.store') }}" enctype="multipart/form-data" class="alur-vertikal">
            @csrf

            @include('bbm.partials.form-transaksi')

            <div class="bilah-aksi">
                <button type="submit" class="tombol tombol--utama">
                    <i class="bi bi-send-check"></i> Simpan Transaksi BBM
                </button>
                <a href="{{ route('bbm.index') }}" class="tombol tombol--garis">Batal</a>
            </div>
        </form>
    </div>
@endsection
