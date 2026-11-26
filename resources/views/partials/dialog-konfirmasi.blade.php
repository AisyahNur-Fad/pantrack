{{-- Dialog konfirmasi bersama. Dibuka oleh pantrack.js untuk <form data-konfirmasi="...">. --}}
<dialog id="dialog-konfirmasi" class="dialog" aria-labelledby="dialog-judul">
    <div class="dialog__badan">
        <div class="dialog__ikon"><i class="bi bi-exclamation-triangle"></i></div>
        <h2 class="dialog__judul" id="dialog-judul" data-dialog-judul>Konfirmasi tindakan</h2>
        <p class="dialog__pesan" data-dialog-pesan></p>
    </div>
    <div class="dialog__aksi">
        <button type="button" class="tombol tombol--garis" data-dialog-batal>Batal</button>
        <button type="button" class="tombol tombol--bahaya" data-dialog-lanjut>Ya, lanjutkan</button>
    </div>
</dialog>
