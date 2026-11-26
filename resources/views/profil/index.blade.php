@extends('layouts.app')

@section('judul', 'Profil Saya & Ganti Kata Sandi')

@section('konten')
    <x-halaman-kepala
        judul="Profil Saya & Keamanan Akun"
        deskripsi="Kelola informasi identitas pribadi, kontak personil, dan perbarui kata sandi akun Anda secara mandiri."
        ikon="bi-person-gear">
    </x-halaman-kepala>

    <div class="grid-form mb-4">
        {{-- Kolom 1: Informasi Identitas & Formulir Kontak --}}
        <div class="alur-vertikal">
            {{-- Kartu Ringkasan Akun --}}
            <div class="kartu p-4">
                <div class="flex-antara item-tengah mb-3">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span class="avatar" style="width: 58px; height: 58px; font-size: 22px;">
                            {{ $pengguna->inisial }}
                        </span>
                        <div>
                            <h2 style="font-size: 20px; font-weight: 800; margin: 0;">{{ $pengguna->nama_lengkap }}</h2>
                            <span class="teks-pudar teks-kecil">{{ '@' . $pengguna->nama_pengguna }}</span>
                        </div>
                    </div>
                    @php
                        $warnaPeran = match ($pengguna->peran?->nama) {
                            'admin' => 'emas',
                            'pekerja_lapangan' => 'biru',
                            'kepala_lapangan' => 'hijau',
                            'atasan' => 'emas',
                            default => 'abu',
                        };
                    @endphp
                    <x-lencana :warna="$warnaPeran" :teks="$pengguna->label_peran" />
                </div>

                <hr class="garis-pemisah">

                <table class="tabel-info mt-2">
                    <tr>
                        <td class="teks-pudar" style="width: 38%">Hak Akses / Peran</td>
                        <td class="teks-tebal">{{ $pengguna->label_peran }}</td>
                    </tr>
                    <tr>
                        <td class="teks-pudar">Status Akun</td>
                        <td>
                            @if ($pengguna->aktif)
                                <span class="teks-sukses"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                            @else
                                <span class="teks-bahaya"><i class="bi bi-x-circle-fill"></i> Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="teks-pudar">Terdaftar Sejak</td>
                        <td>{{ $pengguna->created_at ? $pengguna->created_at->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    @if ($pengguna->adalahPekerjaLapangan())
                        <tr>
                            <td class="teks-pudar">Penugasan Tim</td>
                            <td>
                                @php
                                    $timDipimpin = $pengguna->timYangDipimpin->pluck('nama_tim');
                                    $timAnggota = $pengguna->keanggotaanTim->map(fn($k) => $k->tim?->nama_tim)->filter();
                                    $semuaTim = $timDipimpin->merge($timAnggota)->unique();
                                @endphp
                                @if ($semuaTim->isNotEmpty())
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        @foreach ($semuaTim as $namaTim)
                                            <span class="lencana lencana--hijau">{{ $namaTim }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="teks-pudar">Belum terdaftar di tim</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                </table>
            </div>

            {{-- Formulir Ubah Profil --}}
            <div class="kartu">
                <div class="kartu__kepala">
                    <h3 class="kartu__judul"><i class="bi bi-pencil-square teks-utama"></i> Perbarui Data Identitas</h3>
                </div>
                <div class="kartu__badan">
                    <form method="POST" action="{{ route('profil.update') }}" class="alur-vertikal">
                        @csrf
                        @method('PUT')

                        <x-field label="Nama Lengkap" for="nama_lengkap" :wajib="true">
                            <input type="text" id="nama_lengkap" name="nama_lengkap"
                                   value="{{ old('nama_lengkap', $pengguna->nama_lengkap) }}"
                                   class="masukan @error('nama_lengkap') masukan--error @enderror" required>
                        </x-field>

                        <x-field label="Nama Pengguna (Username)" for="nama_pengguna" :wajib="true"
                                 bantuan="Digunakan saat login. Hanya huruf, angka, strip, dan garis bawah.">
                            <input type="text" id="nama_pengguna" name="nama_pengguna"
                                   value="{{ old('nama_pengguna', $pengguna->nama_pengguna) }}"
                                   class="masukan @error('nama_pengguna') masukan--error @enderror" required>
                        </x-field>

                        <x-field label="Alamat Email (Opsional)" for="email"
                                 bantuan="Bisa digunakan sebagai alternatif username saat login.">
                            <input type="email" id="email" name="email"
                                   value="{{ old('email', $pengguna->email) }}"
                                   class="masukan @error('email') masukan--error @enderror"
                                   placeholder="nama@panlibata.co.id">
                        </x-field>

                        <x-field label="Nomor Telepon / WhatsApp" for="nomor_telepon">
                            <input type="text" id="nomor_telepon" name="nomor_telepon"
                                   value="{{ old('nomor_telepon', $pengguna->nomor_telepon) }}"
                                   class="masukan @error('nomor_telepon') masukan--error @enderror"
                                   placeholder="Contoh: 081234567890">
                        </x-field>

                        <div class="bilah-aksi">
                            <button type="submit" class="tombol tombol--utama">
                                <i class="bi bi-check2-circle"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Kolom 2: Formulir Ganti Kata Sandi --}}
        <div class="alur-vertikal">
            <div class="kartu">
                <div class="kartu__kepala">
                    <h3 class="kartu__judul"><i class="bi bi-shield-lock teks-utama"></i> Ganti Kata Sandi Akun</h3>
                    <span class="lencana lencana--abu">Keamanan</span>
                </div>
                <div class="kartu__badan">
                    <form method="POST" action="{{ route('profil.password') }}" class="alur-vertikal">
                        @csrf
                        @method('PUT')

                        <x-field label="Kata Sandi Saat Ini" for="kata_sandi_lama" :wajib="true"
                                 bantuan="Masukkan kata sandi lama Anda untuk verifikasi identitas.">
                            <input type="password" id="kata_sandi_lama" name="kata_sandi_lama"
                                   class="masukan @error('kata_sandi_lama') masukan--error @enderror"
                                   placeholder="••••••••" required>
                        </x-field>

                        <hr class="garis-pemisah">

                        <x-field label="Kata Sandi Baru" for="kata_sandi" :wajib="true"
                                 bantuan="Minimal 8 karakter. Disarankan kombinasi huruf dan angka.">
                            <input type="password" id="kata_sandi" name="kata_sandi"
                                   class="masukan @error('kata_sandi') masukan--error @enderror"
                                   placeholder="••••••••" required>
                        </x-field>

                        <x-field label="Konfirmasi Kata Sandi Baru" for="kata_sandi_confirmation" :wajib="true"
                                 bantuan="Ketik ulang kata sandi baru untuk memastikan kecocokan.">
                            <input type="password" id="kata_sandi_confirmation" name="kata_sandi_confirmation"
                                   class="masukan"
                                   placeholder="••••••••" required>
                        </x-field>

                        <div class="alert alert--sukses">
                            <i class="bi bi-shield-check"></i>
                            <div>
                                <strong>Tips Keamanan:</strong>
                                <p class="mb-0 teks-kecil">Gunakan kata sandi yang kuat dan jangan bagikan kepada siapapun. Setelah diubah, Anda akan menggunakan kata sandi baru ini untuk login berikutnya.</p>
                            </div>
                        </div>

                        <div class="bilah-aksi">
                            <button type="submit" class="tombol tombol--utama">
                                <i class="bi bi-key-fill"></i> Perbarui Kata Sandi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
