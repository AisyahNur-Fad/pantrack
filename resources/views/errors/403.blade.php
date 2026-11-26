@include('layouts.error', [
    'kode' => 403,
    'judulGalat' => 'Akses ditolak',
    'pesanGalat' => $exception->getMessage() ?: 'Anda tidak memiliki hak akses ke halaman ini.',
])
