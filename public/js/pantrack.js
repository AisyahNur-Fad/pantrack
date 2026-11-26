/* ==========================================================================
   PANTRACK — satu-satunya file JavaScript aplikasi
   Perilaku UI ringan: sidebar mobile, dropdown, notifikasi, dialog konfirmasi,
   tampilkan/sembunyikan kata sandi, dan input angka saja.
   Semua perilaku dipicu lewat atribut data-* pada HTML.
   ========================================================================== */
(function () {
    'use strict';

    const body = document.body;

    /* --- Collapsible Sidebar (Desktop) --- */
    function aturSidebarCiut(ciut) {
        if (ciut) {
            document.documentElement.classList.add('sidebar-menciut');
            document.body.classList.add('sidebar-menciut');
            try { localStorage.setItem('pantrack_sidebar_ciut', '1'); } catch (e) {}
        } else {
            document.documentElement.classList.remove('sidebar-menciut');
            document.body.classList.remove('sidebar-menciut');
            try { localStorage.setItem('pantrack_sidebar_ciut', '0'); } catch (e) {}
        }
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-sidebar-ciut-toggle]')) {
            const sekarangCiut = document.documentElement.classList.contains('sidebar-menciut') || document.body.classList.contains('sidebar-menciut');
            aturSidebarCiut(!sekarangCiut);
        }
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
            e.preventDefault();
            const sekarangCiut = document.documentElement.classList.contains('sidebar-menciut') || document.body.classList.contains('sidebar-menciut');
            aturSidebarCiut(!sekarangCiut);
        }
    });

    /* --- Sidebar mobile: [data-sidebar-toggle] membuka, [data-sidebar-tutup] menutup --- */
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-sidebar-toggle]')) {
            body.classList.toggle('sidebar-terbuka');
        } else if (e.target.closest('[data-sidebar-tutup]')) {
            body.classList.remove('sidebar-terbuka');
        }
    });

    /* --- Dropdown: [data-dropdown] membungkus tombol [data-dropdown-tombol] --- */
    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('[data-dropdown-tombol]');

        document.querySelectorAll('[data-dropdown].dropdown--buka').forEach(function (dd) {
            if (!tombol || dd !== tombol.closest('[data-dropdown]')) {
                dd.classList.remove('dropdown--buka');
            }
        });

        if (tombol) {
            tombol.closest('[data-dropdown]').classList.toggle('dropdown--buka');
        }
    });

    /* --- Escape menutup sidebar dan dropdown --- */
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        body.classList.remove('sidebar-terbuka');
        document.querySelectorAll('[data-dropdown]').forEach(function (dd) { dd.classList.remove('dropdown--buka'); });
    });

    /* --- Notifikasi: tombol tutup dan hilang otomatis untuk pesan sukses --- */
    document.addEventListener('click', function (e) {
        const tutup = e.target.closest('[data-notifikasi-tutup]');
        if (tutup) tutup.closest('.notifikasi').remove();
    });
    document.querySelectorAll('.notifikasi--sukses, .notifikasi--info').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function () { el.remove(); }, 400);
        }, 6000);
    });

    /* --- Dialog konfirmasi: <form data-konfirmasi="Pesan" data-konfirmasi-judul="Judul"> --- */
    const dialog = document.getElementById('dialog-konfirmasi');
    let formMenunggu = null;

    if (dialog) {
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form[data-konfirmasi]');
            if (!form || form.dataset.sudahKonfirmasi === '1') return;

            e.preventDefault();
            formMenunggu = form;
            dialog.querySelector('[data-dialog-judul]').textContent = form.dataset.konfirmasiJudul || 'Konfirmasi tindakan';
            dialog.querySelector('[data-dialog-pesan]').textContent = form.dataset.konfirmasi;
            dialog.showModal();
        });

        dialog.addEventListener('click', function (e) {
            if (e.target.closest('[data-dialog-batal]') || e.target === dialog) {
                formMenunggu = null;
                dialog.close();
            } else if (e.target.closest('[data-dialog-lanjut]') && formMenunggu) {
                formMenunggu.dataset.sudahKonfirmasi = '1';
                formMenunggu.submit();
            }
        });
    }

    /* --- Tampilkan/sembunyikan kata sandi: <button data-lihat-sandi="#id-input"> --- */
    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('[data-lihat-sandi]');
        if (!tombol) return;

        const input = document.querySelector(tombol.dataset.lihatSandi);
        if (!input) return;

        const tersembunyi = input.type === 'password';
        input.type = tersembunyi ? 'text' : 'password';
        tombol.querySelector('i').className = tersembunyi ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    /* --- Input angka saja: <input data-angka> membuang karakter selain 0-9 --- */
    document.addEventListener('input', function (e) {
        if (e.target.matches('[data-angka]')) {
            e.target.value = e.target.value.replace(/\D+/g, '');
        }
    });

    /* --- Mencegah kirim ganda: tombol submit dinonaktifkan sesaat setelah formulir dikirim --- */
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        const tombol = e.target.querySelector('button[type="submit"][data-cegah-ganda]');
        if (tombol) {
            setTimeout(function () { tombol.disabled = true; }, 0);
        }
    });

    /* --- Saklar Toggle Status (Data Master & Kegiatan) --- */
    document.addEventListener('change', function (e) {
        const saklar = e.target.closest('input[data-saklar-status]');
        if (!saklar) return;

        const url = saklar.dataset.url;
        if (!url) return;

        const pembungkus = saklar.closest('.saklar-toggle');
        const labelEl = pembungkus ? pembungkus.querySelector('.saklar-toggle__label') : null;
        const statusSebelumnya = !saklar.checked;
        const labelAktif = saklar.dataset.labelAktif || 'Aktif';
        const labelNonaktif = saklar.dataset.labelNonaktif || 'Tidak aktif';

        // Tampilkan keadaan memuat
        if (pembungkus) pembungkus.classList.add('saklar-toggle--memuat');
        saklar.disabled = true;

        const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content
                          || saklar.form?.querySelector('input[name="_token"]')?.value;

        fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': tokenCsrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _token: tokenCsrf
            })
        })
        .then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) {
                    throw new Error(data.pesan || data.message || 'Gagal mengubah status');
                }
                return data;
            });
        })
        .then(function (data) {
            if (pembungkus) pembungkus.classList.remove('saklar-toggle--memuat');
            saklar.disabled = false;

            const isAktif = Boolean(data.aktif);
            saklar.checked = isAktif;

            if (labelEl) {
                labelEl.textContent = data.label || (isAktif ? labelAktif : labelNonaktif);
                labelEl.className = 'saklar-toggle__label ' + (isAktif ? 'saklar-toggle__label--aktif' : 'saklar-toggle__label--nonaktif');
            }

            tampilkanToast(data.pesan || 'Status berhasil diperbarui.', 'sukses');
        })
        .catch(function (err) {
            if (pembungkus) pembungkus.classList.remove('saklar-toggle--memuat');
            saklar.disabled = false;
            saklar.checked = statusSebelumnya;

            tampilkanToast(err.message || 'Terjadi kesalahan saat mengubah status.', 'galat');
        });
    });

    /* --- Helper Toast Notifikasi Cepat --- */
    function tampilkanToast(pesan, jenis) {
        let wadah = document.querySelector('.toast-wadah');
        if (!wadah) {
            wadah = document.createElement('div');
            wadah.className = 'toast-wadah';
            document.body.appendChild(wadah);
        }

        const ikon = jenis === 'sukses' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        const kelasJenis = jenis === 'sukses' ? 'toast--sukses' : 'toast--galat';

        const toast = document.createElement('div');
        toast.className = 'toast ' + kelasJenis;
        toast.innerHTML = '<i class="bi ' + ikon + '"></i><div class="toast__isi">' + escapeHtml(pesan) + '</div><button type="button" class="toast__tutup" aria-label="Tutup">&times;</button>';

        wadah.appendChild(toast);

        toast.querySelector('.toast__tutup').addEventListener('click', function () {
            toast.remove();
        });

        setTimeout(function () {
            toast.style.transition = 'opacity .35s, transform .35s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(function () { toast.remove(); }, 350);
        }, 4000);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
})();
