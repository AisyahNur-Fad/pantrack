{{--
    Komponen field formulir PANTRACK.
    Mendukung dua mode penggunaan:
    1. Mode otomatis (Master): <x-field :field="$f" :record="$record" />
    2. Mode fleksibel/slot: <x-field label="Nama" for="nama" :wajib="true" bantuan="..."> <input ...> </x-field>
--}}
@props([
    'field' => null,
    'record' => null,
    'label' => null,
    'for' => null,
    'wajib' => false,
    'bantuan' => null,
    'lebar' => null,
])

@if ($field)
    @php
        $nama = $field['name'];
        $tipe = $field['tipe'] ?? 'teks';
        $nilai = old($nama, $record?->{$nama} ?? ($field['default'] ?? null));
        $nilai = $nilai instanceof \Carbon\CarbonInterface ? $nilai->format('Y-m-d') : $nilai;
        $adaGalat = $errors->has($nama);
        $kelas = 'masukan block w-full rounded-lg border text-sm transition-all duration-150 ' .
                 ($adaGalat
                    ? 'border-rose-300 bg-rose-50/50 text-rose-900 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                    : 'border-slate-300 bg-white text-slate-800 hover:border-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20');
        $id = 'f-'.$nama;
        // Kata sandi tidak boleh diisi ulang otomatis; pada tambah data wajib, pada ubah opsional.
        $fieldWajib = ($field['wajib'] ?? false) || ($nama === 'kata_sandi' && ! $record);
    @endphp

    <div class="form-grup flex flex-col gap-1.5 {{ ($field['lebar'] ?? '') === 'penuh' ? 'form-grup--penuh col-span-full' : '' }}">
        @if ($tipe === 'centang')
            <label class="centang flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer transition-colors" for="{{ $id }}">
                <input type="hidden" name="{{ $nama }}" value="0">
                <input type="checkbox" id="{{ $id }}" name="{{ $nama }}" value="1" @checked((bool) $nilai) class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-xs sm:text-sm font-medium text-slate-700 select-none">{{ $field['label'] }}</span>
            </label>
        @else
            <label class="form-label text-xs sm:text-sm font-semibold text-slate-700 flex items-center gap-1" for="{{ $id }}">
                <span>{{ $field['label'] }}</span>
                @if ($fieldWajib)<span class="wajib text-rose-500">*</span>@endif
            </label>

            @switch($tipe)
                @case('area')
                    <textarea id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} p-3 min-h-[96px]" rows="3">{{ $nilai }}</textarea>
                    @break

                @case('pilih')
                    <select id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} h-10 px-3 py-2 cursor-pointer" @required($fieldWajib)>
                        @if (! $fieldWajib || isset($field['kosong']))
                            <option value="">{{ $field['kosong'] ?? '— Pilih —' }}</option>
                        @else
                            <option value="">— Pilih —</option>
                        @endif
                        @foreach ($field['options'] as $nilaiOpsi => $labelOpsi)
                            <option value="{{ $nilaiOpsi }}" @selected((string) $nilai === (string) $nilaiOpsi)>{{ $labelOpsi }}</option>
                        @endforeach
                    </select>
                    @break

                @case('password')
                    <div class="kolom-sandi relative">
                        <input type="password" id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} h-10 px-3 pr-10" autocomplete="new-password">
                        <button type="button" class="kolom-sandi__lihat absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1" data-lihat-sandi="#{{ $id }}" aria-label="Tampilkan kata sandi">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @break

                @case('telepon')
                    <input type="tel" id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} h-10 px-3" value="{{ $nilai }}" inputmode="numeric" maxlength="20" data-angka>
                    @break

                @case('angka')
                    <input type="number" id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} h-10 px-3" value="{{ $nilai }}" step="{{ $field['step'] ?? '1' }}" min="0">
                    @break

                @case('tanggal')
                    <input type="date" id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} h-10 px-3" value="{{ $nilai }}">
                    @break

                @case('email')
                    <input type="email" id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} h-10 px-3" value="{{ $nilai }}">
                    @break

                @default
                    <input type="text" id="{{ $id }}" name="{{ $nama }}" class="{{ $kelas }} h-10 px-3" value="{{ $nilai }}" @required($fieldWajib)>
            @endswitch
        @endif

        @isset($field['bantuan'])
            <span class="form-bantuan text-xs text-slate-500 leading-normal">{{ $field['bantuan'] }}</span>
        @endisset

        @error($nama)
            <span class="form-galat text-xs font-medium text-rose-600 flex items-center gap-1 mt-0.5"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
        @enderror
    </div>
@else
    {{-- Mode fleksibel / container slot --}}
    <div class="form-grup flex flex-col gap-1.5 {{ $lebar === 'penuh' ? 'form-grup--penuh col-span-full' : '' }}">
        @if ($label)
            <label class="form-label text-xs sm:text-sm font-semibold text-slate-700 flex items-center gap-1" @if($for) for="{{ $for }}" @endif>
                <span>{{ $label }}</span>
                @if ($wajib)<span class="wajib text-rose-500">*</span>@endif
            </label>
        @endif

        {{ $slot }}

        @if ($bantuan)
            <span class="form-bantuan text-xs text-slate-500 leading-normal">{{ $bantuan }}</span>
        @endif

        @if ($for && $errors->has($for))
            <span class="form-galat text-xs font-medium text-rose-600 flex items-center gap-1 mt-0.5"><i class="bi bi-exclamation-circle"></i> {{ $errors->first($for) }}</span>
        @endif
    </div>
@endif
