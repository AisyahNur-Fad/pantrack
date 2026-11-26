# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PANTRACK

Panlibata Tracking System

Sistem Monitoring Realisasi Pekerjaan Drone Agrikultur Berbasis Website

Jenis Produk

Website operasional

Pengguna

4 peran pengguna

Backend

Laravel / PHP

Database

MySQL

Platform

Browser desktop dan ponsel

Status dokumen

Rancangan kebutuhan awal

## 1. Ringkasan produk

PANTRACK adalah aplikasi berbasis website untuk mencatat, memantau, memeriksa, dan merekap realisasi pekerjaan drone agrikultur. Sistem dirancang untuk membantu PT. Panlibata Andalas Bertuah mengelola laporan pekerjaan lapangan dan BBM secara lebih terstruktur.

Saat ini, pekerja mengirimkan laporan melalui WhatsApp, kemudian admin memindahkan data ke Excel. Proses tersebut berpotensi menyebabkan keterlambatan pencatatan, kesalahan penyalinan, data yang terlewat, dan kesulitan memantau perkembangan pekerjaan.

PANTRACK akan menjadikan database sebagai sumber data utama. Pekerja memasukkan data melalui formulir website, kepala lapangan memeriksa SPK dan laporan realisasi, admin memeriksa transaksi BBM dan mengekspor laporan, sedangkan atasan memantau hasil operasional.

### Tujuan utama

- Mengurangi pencatatan ulang dari WhatsApp ke Excel.
- Mempermudah pelaporan realisasi pekerjaan per petak dan tanggal.
- Memisahkan target pekerjaan berdasarkan SPK dari luas realisasi aktual.
- Memantau stok BBM secara terpisah untuk setiap tim drone.
- Menerapkan alur pemeriksaan dan persetujuan sesuai tanggung jawab pengguna.
- Menghasilkan laporan Excel berdasarkan data yang sudah tersimpan dan disetujui.

## 2. Permasalahan yang ingin diselesaikan

| Permasalahan saat ini                         | Solusi PANTRACK                             |
| --------------------------------------------- | ------------------------------------------- |
| Laporan dikirim melalui pesan WhatsApp        | Formulir pelaporan terstruktur              |
| Admin harus mengetik ulang laporan ke Excel   | Rekapitulasi dan ekspor Excel otomatis      |
| Data petak dan status pekerjaan sulit direkap | Data setiap petak tersimpan secara terpisah |
| SPK perlu diperiksa sebelum digunakan         | Alur pemeriksaan dan persetujuan SPK        |
| Pencatatan stok BBM berpotensi tidak akurat   | Pencatatan transaksi dan perhitungan stok   |
| Stok BBM antar-tim harus tetap terpisah       | Setiap transaksi dikaitkan dengan tim drone |
| Bukti penerimaan atau penggunaan BBM tersebar | Lampiran bukti tersimpan pada transaksi     |
| Sulit mengetahui laporan yang belum diperiksa | Dashboard status laporan dan verifikasi     |

## 3. Ruang lingkup produk

### 3.1 Fitur yang termasuk dalam versi awal

- Login dan pengelolaan hak akses.
- Data master drone, tim, anggota tim, distrik, petak, dan jenis pekerjaan.
- Penginputan dan persetujuan SPK.
- Pencatatan realisasi pekerjaan dengan beberapa petak dalam satu formulir.
- Pemeriksaan dan persetujuan laporan realisasi.
- Pencatatan pengajuan, penerimaan, dan penggunaan BBM.
- Pemeriksaan transaksi BBM oleh admin.
- Perhitungan stok BBM per tim.
- Dashboard operasional.
- Ekspor laporan Excel.
- Lampiran dokumen dan foto bukti.
- Riwayat aktivitas pengguna.

### 3.2 Fitur di luar ruang lingkup awal

- Pengendalian penerbangan drone.
- Pelacakan GPS atau telemetri drone secara langsung.
- Integrasi langsung dengan perangkat drone.
- Penggantian sistem navigasi atau perangkat lunak penerbangan.
- Pengiriman notifikasi WhatsApp otomatis.
- Modul akuntansi lengkap dan penggajian.
- Modul kasbon, biaya operasional terperinci, dan suku cadang sebelum kebutuhan serta alurnya ditetapkan lebih lanjut.

## 4. Pengguna dan hak akses

PANTRACK memiliki empat peran pengguna. Setiap peran memiliki tanggung jawab dan batas akses yang berbeda.

| Fitur                         | Admin             | Pekerja Lapangan  | Kepala Lapangan                | Atasan            |
| ----------------------------- | ----------------- | ----------------- | ------------------------------ | ----------------- |
| Login                         | Ya                | Ya                | Ya                             | Ya                |
| Mengelola data master         | Ya                | Tidak             | Tidak                          | Tidak             |
| Menginput SPK                 | Tidak             | Ya                | Ya                             | Tidak             |
| Memeriksa dan menyetujui SPK  | Tidak             | Tidak             | Ya                             | Tidak             |
| Menginput realisasi pekerjaan | Tidak             | Ya                | Dapat membantu sesuai prosedur | Tidak             |
| Memeriksa realisasi           | Tidak             | Tidak             | Ya                             | Memantau          |
| Menginput transaksi BBM       | Tidak             | Ya                | Tidak wajib                    | Tidak             |
| Memeriksa transaksi BBM       | Ya                | Tidak             | Tidak                          | Memantau          |
| Melihat dashboard             | Ya                | Sesuai tim        | Sesuai wilayah pengawasan      | Ya                |
| Mengekspor Excel              | Ya                | Tidak             | Tidak                          | Melihat laporan   |
| Melihat riwayat aktivitas     | Sesuai kewenangan | Aktivitas sendiri | Sesuai kewenangan              | Sesuai kewenangan |

Ketentuan khusus:

- Kepala lapangan boleh menginput sekaligus menyetujui SPK yang sama. Sistem wajib mencatat kedua aktivitas tersebut.
- Kepala lapangan dapat membantu menginput SPK ketika pekerja tidak mengetahui detailnya.
- Helper tidak diwajibkan memiliki akun atau terdaftar dalam data master personel.
- Admin memeriksa transaksi BBM, sedangkan kepala lapangan memeriksa SPK dan laporan realisasi.
- Atasan berfungsi untuk memantau, bukan mengubah data operasional.

Hak akses perlu diterapkan di sisi backend Laravel menggunakan middleware, policy, atau mekanisme otorisasi setara, bukan hanya menyembunyikan tombol pada tampilan.

## 5. Kebutuhan fungsional

Setiap fitur diberi kode agar mudah digunakan dalam pengembangan, pengujian, dan penyusunan laporan penelitian.

### 5.1 Autentikasi dan pengguna

FR-01

Login dan logout

Pengguna dapat masuk dan keluar menggunakan akun yang terdaftar.

FR-02

Pengelolaan pengguna

Admin dapat menambahkan, mengubah, menonaktifkan, dan menetapkan peran akun sesuai kebutuhan.

FR-03

Pembatasan akses

Sistem menolak akses ke halaman atau tindakan yang tidak sesuai dengan peran pengguna.

### 5.2 Data master

Admin mengelola data pendukung operasional berikut:

| Data            | Informasi yang disimpan                                          |
| --------------- | ---------------------------------------------------------------- |
| Drone           | Nomor drone, tipe drone, status operasional                      |
| Tim             | Nama tim, drone yang menjadi tanggung jawab, distrik, status tim |
| Anggota tim     | Nama, peran dalam tim, periode penugasan                         |
| Distrik         | Nama distrik dan keterangan                                      |
| Petak           | Kode petak, distrik, jenis lahan, luas petak                     |
| Jenis pekerjaan | Kode dan nama pekerjaan                                          |
| Jenis BBM       | Nama BBM dan satuan                                              |
| Tarif pekerjaan | Jenis pekerjaan, nilai tarif, tanggal mulai dan akhir berlakunya |

Sistem harus dapat menyimpan riwayat tarif sehingga perubahan tarif tidak mengubah nilai transaksi pekerjaan yang sudah disetujui.

### 5.3 Pengelolaan SPK

FR-04 — Input SPK

Pekerja lapangan dan kepala lapangan dapat memasukkan data SPK. Data mencakup nomor SPK, tanggal, periode, distrik, jenis pekerjaan, daftar petak, target luas, dan lampiran dokumen jika tersedia.

FR-05 — Pemeriksaan SPK

Kepala lapangan dapat memeriksa SPK, menyetujuinya, atau mengembalikannya untuk diperbaiki.

FR-06 — Riwayat SPK

Sistem mencatat penginput, penyetuju, waktu persetujuan, perubahan, dan alasan pengembalian.

FR-07 — Penggunaan SPK

SPK yang belum disetujui tidak boleh menjadi dasar laporan realisasi final. Sistem perlu memberikan peringatan apabila pengguna mencoba menggunakannya sebelum disetujui.

Status SPK yang digunakan:

- Menunggu persetujuan.
- Perlu perbaikan.
- Disetujui.
- Ditolak, jika perusahaan menghendaki penolakan permanen.

Satu SPK dapat memiliki beberapa petak dengan target luas masing-masing. Target tersebut harus dibedakan dari luas pekerjaan aktual.

### 5.4 Laporan realisasi pekerjaan

FR-08 — Formulir laporan

Pekerja mengisi identitas laporan, termasuk tim drone, periode, distrik, pilot, co-pilot, dan helper. Data anggota tim dapat mengikuti penugasan yang berlaku atau disesuaikan untuk laporan tertentu.

FR-09 — Banyak petak dalam satu formulir

Pengguna dapat menambahkan beberapa petak dalam satu laporan menggunakan tombol Tambah Petak. Setiap petak memiliki data tanggal pekerjaan, kode petak, luas realisasi, jenis pekerjaan, status, dan keterangan kendala.

FR-10 — Penyimpanan dan pengajuan

Laporan dapat disimpan sebagai draf sebelum diajukan untuk diperiksa kepala lapangan.

FR-11 — Pemeriksaan realisasi

Kepala lapangan dapat menyetujui atau mengembalikan laporan untuk diperbaiki, dengan catatan yang jelas.

FR-12 — Riwayat laporan

Sistem menyimpan status laporan, waktu pengajuan, pemeriksa, waktu persetujuan, dan riwayat perubahan.

Status pekerjaan perlu dapat menggambarkan kondisi operasional pada contoh laporan, seperti:

| Istilah Operasional     | Kategori atau Status dalam Sistem                |
| ----------------------- | ------------------------------------------------ |
| Closing                 | Selesai                                          |
| Progres                 | Dalam proses                                     |
| Ketek Rusak             | Terkendala — kerusakan ketek                     |
| Menunggu Mapping        | Menunggu pemetaan                                |
| Mapping Petak           | Proses pemetaan                                  |
| Menunggu Flight Mission | Menunggu misi penerbangan                        |
| MRS Belom Jadi          | Status khusus yang perlu dikonfirmasi perusahaan |

Istilah khusus sebaiknya dapat dikelola atau dipetakan melalui data referensi agar tidak menghilangkan bahasa operasional lapangan.

### 5.5 Monitoring pencapaian pekerjaan

FR-13 — Perhitungan luas harian dan bulanan

Sistem menghitung luas realisasi harian (To Day) dan akumulasi bulan berjalan (MTD) dari detail pekerjaan yang memenuhi kriteria perhitungan.

FR-14 — Pencegahan perhitungan ganda

Sistem harus dapat membedakan laporan baru, pembaruan laporan, dan pekerjaan berulang pada petak yang sama. Penjumlahan tidak boleh dilakukan hanya berdasarkan kode petak tanpa memperhatikan transaksi realisasinya.

FR-15 — Monitoring status

Pengguna dapat melihat jumlah dan luas pekerjaan berdasarkan status, distrik, tim, drone, dan periode sesuai hak akses.

### 5.6 Monitoring BBM

FR-16 — Pengajuan BBM

Pekerja menginput pengajuan BBM untuk tim yang menjadi tanggung jawabnya.

FR-17 — Penerimaan BBM

Pekerja mencatat jumlah BBM yang benar-benar diterima, tanggal penerimaan, jenis BBM, dan bukti pendukung jika tersedia atau diwajibkan.

FR-18 — Penggunaan BBM

Pekerja mencatat tanggal penggunaan, jumlah liter, keperluan seperti mobil atau genset, dan bukti pendukung sesuai kebijakan perusahaan.

FR-19 — Pemeriksaan admin

Admin memeriksa transaksi BBM dan dapat memverifikasi atau mengembalikannya untuk diperbaiki.

FR-20 — Saldo per tim

Sistem menghitung stok setiap tim secara terpisah. Transaksi satu tim tidak boleh mengubah saldo tim lain.

FR-21 — Riwayat BBM

Sistem menampilkan transaksi masuk, keluar, pengajuan, status verifikasi, dan saldo berdasarkan periode.

Aturan perhitungan saldo resmi:

\\[ \begin{aligned} \text{Stok akhir}={}&\text{Stok awal}\\\ &+\text{Penerimaan terverifikasi}\\\ &+\text{Penyesuaian masuk}\\\ &-\text{Penggunaan terverifikasi}\\\ &-\text{Penyesuaian keluar} \end{aligned} \\]

Pengajuan BBM tidak menambah stok. Stok bertambah ketika penerimaan aktual dicatat dan diverifikasi. Transaksi yang belum diverifikasi ditampilkan sebagai transaksi menunggu pemeriksaan, bukan sebagai perubahan stok resmi.

### 5.7 Dashboard dan ekspor Excel

FR-22 — Dashboard operasional

Menampilkan ringkasan luas realisasi, status petak, laporan menunggu pemeriksaan, dan ringkasan BBM sesuai hak akses.

FR-23 — Filter laporan

Pengguna yang berwenang dapat menyaring data berdasarkan periode, distrik, tim, drone, jenis pekerjaan, dan status.

FR-24 — Ekspor Excel

Admin dapat memilih periode dan mengekspor data yang sudah memenuhi kriteria persetujuan ke dalam format Excel.

FR-25 — Riwayat ekspor

Sistem menyimpan nama berkas, periode, waktu ekspor, pengguna yang mengekspor, dan jumlah baris.

Format laporan Excel harus mempertahankan kolom perusahaan:

`TGL`, `SPK`, `LUASAN SPK`, `Ha`, `PEKERJAAN`, `PETAK`, `LUAS`, `Ha`, `HARGA`, `TOTAL`, dan `CHECK`.

Arti setiap kolom dan dasar perhitungan `TOTAL` perlu mengikuti format resmi perusahaan. Sistem tidak boleh menyamakan luas target SPK dengan luas realisasi tanpa pemeriksaan.

## 6. Alur kerja utama sistem

### 6.1 Alur SPK

\#chatgpt-mermaid-\_r_523\_{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:16px;fill:rgb(13, 13, 13);}@keyframes edge-animation-frame{from{stroke-dashoffset:0;}}@keyframes dash{to{stroke-dashoffset:0;}}#chatgpt-mermaid-\_r_523\_ .edge-animation-slow{stroke-dasharray:9,5!important;stroke-dashoffset:900;animation:dash 50s linear infinite;stroke-linecap:round;}#chatgpt-mermaid-\_r_523\_ .edge-animation-fast{stroke-dasharray:9,5!important;stroke-dashoffset:900;animation:dash 20s linear infinite;stroke-linecap:round;}#chatgpt-mermaid-\_r_523\_ .error-icon{fill:rgb(243, 243, 243);}#chatgpt-mermaid-\_r_523\_ .error-text{fill:rgb(13, 13, 13);stroke:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ .edge-thickness-normal{stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ .edge-thickness-thick{stroke-width:3.5px;}#chatgpt-mermaid-\_r_523\_ .edge-pattern-solid{stroke-dasharray:0;}#chatgpt-mermaid-\_r_523\_ .edge-thickness-invisible{stroke-width:0;fill:none;}#chatgpt-mermaid-\_r_523\_ .edge-pattern-dashed{stroke-dasharray:3;}#chatgpt-mermaid-\_r_523\_ .edge-pattern-dotted{stroke-dasharray:2;}#chatgpt-mermaid-\_r_523\_ .marker{fill:rgb(143, 143, 143);stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_523\_ .marker.cross{stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_523\_ svg{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:16px;}#chatgpt-mermaid-\_r_523\_ p{margin:0;}#chatgpt-mermaid-\_r_523\_ .label{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ .cluster-label text{fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ .cluster-label span{color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ .cluster-label span p{background-color:transparent;}#chatgpt-mermaid-\_r_523\_ .label text,#chatgpt-mermaid-\_r_523\_ span{fill:rgb(13, 13, 13);color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ .node rect,#chatgpt-mermaid-\_r_523\_ .node circle,#chatgpt-mermaid-\_r_523\_ .node ellipse,#chatgpt-mermaid-\_r_523\_ .node polygon,#chatgpt-mermaid-\_r_523\_ .node path{fill:rgb(222, 234, 251);stroke:rgb(83, 154, 248);stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ .rough-node .label text,#chatgpt-mermaid-\_r_523\_ .node .label text,#chatgpt-mermaid-\_r_523\_ .image-shape .label,#chatgpt-mermaid-\_r_523\_ .icon-shape .label{text-anchor:middle;}#chatgpt-mermaid-\_r_523\_ .node .katex path{fill:#000;stroke:#000;stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ .rough-node .label,#chatgpt-mermaid-\_r_523\_ .node .label,#chatgpt-mermaid-\_r_523\_ .image-shape .label,#chatgpt-mermaid-\_r_523\_ .icon-shape .label{text-align:center;}#chatgpt-mermaid-\_r_523\_ .node.clickable{cursor:pointer;}#chatgpt-mermaid-\_r_523\_ .root .anchor path{fill:rgb(143, 143, 143)!important;stroke-width:0;stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_523\_ .arrowheadPath{fill:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_523\_ .edgePath .path{stroke:rgb(143, 143, 143);stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ .flowchart-link{stroke:rgb(143, 143, 143);fill:none;}#chatgpt-mermaid-\_r_523\_ .edgeLabel{background-color:rgb(252, 252, 252);text-align:center;}#chatgpt-mermaid-\_r_523\_ .edgeLabel p{background-color:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_523\_ .edgeLabel rect{opacity:0.5;background-color:rgb(252, 252, 252);fill:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_523\_ .labelBkg{background-color:rgba(252, 252, 252, 0.5);}#chatgpt-mermaid-\_r_523\_ .cluster rect{fill:rgb(243, 243, 243);stroke:rgba(0, 0, 0, 0.1);stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ .cluster text{fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ .cluster span{color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ div.mermaidTooltip{position:absolute;text-align:center;max-width:200px;padding:2px;font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:12px;background:rgb(243, 243, 243);border:1px solid rgba(0, 0, 0, 0.1);border-radius:2px;pointer-events:none;z-index:100;}#chatgpt-mermaid-\_r_523\_ .flowchartTitleText{text-anchor:middle;font-size:18px;fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_523\_ rect.text{fill:none;stroke-width:0;}#chatgpt-mermaid-\_r_523\_ .icon-shape,#chatgpt-mermaid-\_r_523\_ .image-shape{background-color:rgb(252, 252, 252);text-align:center;}#chatgpt-mermaid-\_r_523\_ .icon-shape p,#chatgpt-mermaid-\_r_523\_ .image-shape p{background-color:rgb(252, 252, 252);padding:2px;}#chatgpt-mermaid-\_r_523\_ .icon-shape .label rect,#chatgpt-mermaid-\_r_523\_ .image-shape .label rect{opacity:0.5;background-color:rgb(252, 252, 252);fill:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_523\_ .label-icon{display:inline-block;height:1em;overflow:visible;vertical-align:-0.125em;}#chatgpt-mermaid-\_r_523\_ .node .label-icon path{fill:currentColor;stroke:revert;stroke-width:revert;}#chatgpt-mermaid-\_r_523\_ .node .neo-node{stroke:rgb(83, 154, 248);}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].node rect,#chatgpt-mermaid-\_r_523\_ [data-look="neo"].cluster rect,#chatgpt-mermaid-\_r_523\_ [data-look="neo"].node polygon{stroke:url(#chatgpt-mermaid-\_r_523\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].swimlane.cluster rect{filter:none;}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].node path{stroke:url(#chatgpt-mermaid-\_r_523\_-gradient);stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].node .outer-path{filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].node .neo-line path{stroke:rgb(83, 154, 248);filter:none;}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].node circle{stroke:url(#chatgpt-mermaid-\_r_523\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].node circle .state-start{fill:#000000;}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].icon-shape .icon{fill:url(#chatgpt-mermaid-\_r_523\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_523\_ [data-look="neo"].icon-shape .icon-neo path{stroke:url(#chatgpt-mermaid-\_r_523\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_523\_ .node text{font-size:14px;font-weight:600;letter-spacing:normal;fill:rgb(0, 79, 153);}#chatgpt-mermaid-\_r_523\_ .edgeLabels text{font-size:13px;font-weight:600;letter-spacing:-0.08px;fill:rgb(0, 79, 153);}#chatgpt-mermaid-\_r_523\_ .node tspan[font-weight="normal"],#chatgpt-mermaid-\_r_523\_ .edgeLabels tspan[font-weight="normal"]{font-weight:600;}#chatgpt-mermaid-\_r_523\_ .edgeLabel .label rect{opacity:1;rx:13px;ry:13px;fill:rgb(245, 250, 255);stroke:rgb(206, 219, 229);stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ .node rect,#chatgpt-mermaid-\_r_523\_ .node circle,#chatgpt-mermaid-\_r_523\_ .node ellipse,#chatgpt-mermaid-\_r_523\_ .node polygon,#chatgpt-mermaid-\_r_523\_ .node path{fill:rgb(229, 243, 255);stroke:rgba(0, 0, 0, 0.1);stroke-width:1px;}#chatgpt-mermaid-\_r_523\_ .node rect{rx:16px;ry:16px;}#chatgpt-mermaid-\_r_523\_ .node.mermaid-decision .label-container{fill:rgb(245, 250, 255);stroke:rgb(206, 219, 229);stroke-dasharray:2,2;}#chatgpt-mermaid-\_r_523\_ .edgePaths .flowchart-link{stroke:rgb(143, 143, 143);stroke-width:1px;stroke-linecap:round;stroke-linejoin:round;}#chatgpt-mermaid-\_r_523\_ .marker{fill:rgb(143, 143, 143);stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_523\_ :root{--mermaid-font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";}MulaiSPK diterima oleh timSiapa yang menginput?Pekerja menginput SPKKepala lapangan membantumenginputSPK menunggu pemeriksaanKepala lapangan memeriksaSPKData sudah sesuai?Dikembalikan untuk diperbaikiSPK disetujuiSPK digunakan untuk realisasiSelesaiPekerjaKepala lapanganBelumYa

Kepala lapangan boleh menjadi penginput sekaligus penyetuju. Kedua aktivitas tersebut tetap dicatat secara terpisah dalam riwayat.

### 6.2 Alur realisasi pekerjaan

\#chatgpt-mermaid-\_r_52c\_{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:16px;fill:rgb(13, 13, 13);}@keyframes edge-animation-frame{from{stroke-dashoffset:0;}}@keyframes dash{to{stroke-dashoffset:0;}}#chatgpt-mermaid-\_r_52c\_ .edge-animation-slow{stroke-dasharray:9,5!important;stroke-dashoffset:900;animation:dash 50s linear infinite;stroke-linecap:round;}#chatgpt-mermaid-\_r_52c\_ .edge-animation-fast{stroke-dasharray:9,5!important;stroke-dashoffset:900;animation:dash 20s linear infinite;stroke-linecap:round;}#chatgpt-mermaid-\_r_52c\_ .error-icon{fill:rgb(243, 243, 243);}#chatgpt-mermaid-\_r_52c\_ .error-text{fill:rgb(13, 13, 13);stroke:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ .edge-thickness-normal{stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ .edge-thickness-thick{stroke-width:3.5px;}#chatgpt-mermaid-\_r_52c\_ .edge-pattern-solid{stroke-dasharray:0;}#chatgpt-mermaid-\_r_52c\_ .edge-thickness-invisible{stroke-width:0;fill:none;}#chatgpt-mermaid-\_r_52c\_ .edge-pattern-dashed{stroke-dasharray:3;}#chatgpt-mermaid-\_r_52c\_ .edge-pattern-dotted{stroke-dasharray:2;}#chatgpt-mermaid-\_r_52c\_ .marker{fill:rgb(143, 143, 143);stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52c\_ .marker.cross{stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52c\_ svg{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:16px;}#chatgpt-mermaid-\_r_52c\_ p{margin:0;}#chatgpt-mermaid-\_r_52c\_ .label{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ .cluster-label text{fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ .cluster-label span{color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ .cluster-label span p{background-color:transparent;}#chatgpt-mermaid-\_r_52c\_ .label text,#chatgpt-mermaid-\_r_52c\_ span{fill:rgb(13, 13, 13);color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ .node rect,#chatgpt-mermaid-\_r_52c\_ .node circle,#chatgpt-mermaid-\_r_52c\_ .node ellipse,#chatgpt-mermaid-\_r_52c\_ .node polygon,#chatgpt-mermaid-\_r_52c\_ .node path{fill:rgb(222, 234, 251);stroke:rgb(83, 154, 248);stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ .rough-node .label text,#chatgpt-mermaid-\_r_52c\_ .node .label text,#chatgpt-mermaid-\_r_52c\_ .image-shape .label,#chatgpt-mermaid-\_r_52c\_ .icon-shape .label{text-anchor:middle;}#chatgpt-mermaid-\_r_52c\_ .node .katex path{fill:#000;stroke:#000;stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ .rough-node .label,#chatgpt-mermaid-\_r_52c\_ .node .label,#chatgpt-mermaid-\_r_52c\_ .image-shape .label,#chatgpt-mermaid-\_r_52c\_ .icon-shape .label{text-align:center;}#chatgpt-mermaid-\_r_52c\_ .node.clickable{cursor:pointer;}#chatgpt-mermaid-\_r_52c\_ .root .anchor path{fill:rgb(143, 143, 143)!important;stroke-width:0;stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52c\_ .arrowheadPath{fill:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52c\_ .edgePath .path{stroke:rgb(143, 143, 143);stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ .flowchart-link{stroke:rgb(143, 143, 143);fill:none;}#chatgpt-mermaid-\_r_52c\_ .edgeLabel{background-color:rgb(252, 252, 252);text-align:center;}#chatgpt-mermaid-\_r_52c\_ .edgeLabel p{background-color:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_52c\_ .edgeLabel rect{opacity:0.5;background-color:rgb(252, 252, 252);fill:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_52c\_ .labelBkg{background-color:rgba(252, 252, 252, 0.5);}#chatgpt-mermaid-\_r_52c\_ .cluster rect{fill:rgb(243, 243, 243);stroke:rgba(0, 0, 0, 0.1);stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ .cluster text{fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ .cluster span{color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ div.mermaidTooltip{position:absolute;text-align:center;max-width:200px;padding:2px;font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:12px;background:rgb(243, 243, 243);border:1px solid rgba(0, 0, 0, 0.1);border-radius:2px;pointer-events:none;z-index:100;}#chatgpt-mermaid-\_r_52c\_ .flowchartTitleText{text-anchor:middle;font-size:18px;fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52c\_ rect.text{fill:none;stroke-width:0;}#chatgpt-mermaid-\_r_52c\_ .icon-shape,#chatgpt-mermaid-\_r_52c\_ .image-shape{background-color:rgb(252, 252, 252);text-align:center;}#chatgpt-mermaid-\_r_52c\_ .icon-shape p,#chatgpt-mermaid-\_r_52c\_ .image-shape p{background-color:rgb(252, 252, 252);padding:2px;}#chatgpt-mermaid-\_r_52c\_ .icon-shape .label rect,#chatgpt-mermaid-\_r_52c\_ .image-shape .label rect{opacity:0.5;background-color:rgb(252, 252, 252);fill:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_52c\_ .label-icon{display:inline-block;height:1em;overflow:visible;vertical-align:-0.125em;}#chatgpt-mermaid-\_r_52c\_ .node .label-icon path{fill:currentColor;stroke:revert;stroke-width:revert;}#chatgpt-mermaid-\_r_52c\_ .node .neo-node{stroke:rgb(83, 154, 248);}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].node rect,#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].cluster rect,#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].node polygon{stroke:url(#chatgpt-mermaid-\_r_52c\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].swimlane.cluster rect{filter:none;}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].node path{stroke:url(#chatgpt-mermaid-\_r_52c\_-gradient);stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].node .outer-path{filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].node .neo-line path{stroke:rgb(83, 154, 248);filter:none;}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].node circle{stroke:url(#chatgpt-mermaid-\_r_52c\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].node circle .state-start{fill:#000000;}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].icon-shape .icon{fill:url(#chatgpt-mermaid-\_r_52c\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52c\_ [data-look="neo"].icon-shape .icon-neo path{stroke:url(#chatgpt-mermaid-\_r_52c\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52c\_ .node text{font-size:14px;font-weight:600;letter-spacing:normal;fill:rgb(0, 79, 153);}#chatgpt-mermaid-\_r_52c\_ .edgeLabels text{font-size:13px;font-weight:600;letter-spacing:-0.08px;fill:rgb(0, 79, 153);}#chatgpt-mermaid-\_r_52c\_ .node tspan[font-weight="normal"],#chatgpt-mermaid-\_r_52c\_ .edgeLabels tspan[font-weight="normal"]{font-weight:600;}#chatgpt-mermaid-\_r_52c\_ .edgeLabel .label rect{opacity:1;rx:13px;ry:13px;fill:rgb(245, 250, 255);stroke:rgb(206, 219, 229);stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ .node rect,#chatgpt-mermaid-\_r_52c\_ .node circle,#chatgpt-mermaid-\_r_52c\_ .node ellipse,#chatgpt-mermaid-\_r_52c\_ .node polygon,#chatgpt-mermaid-\_r_52c\_ .node path{fill:rgb(229, 243, 255);stroke:rgba(0, 0, 0, 0.1);stroke-width:1px;}#chatgpt-mermaid-\_r_52c\_ .node rect{rx:16px;ry:16px;}#chatgpt-mermaid-\_r_52c\_ .node.mermaid-decision .label-container{fill:rgb(245, 250, 255);stroke:rgb(206, 219, 229);stroke-dasharray:2,2;}#chatgpt-mermaid-\_r_52c\_ .edgePaths .flowchart-link{stroke:rgb(143, 143, 143);stroke-width:1px;stroke-linecap:round;stroke-linejoin:round;}#chatgpt-mermaid-\_r_52c\_ .marker{fill:rgb(143, 143, 143);stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52c\_ :root{--mermaid-font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";}MulaiPekerja memilih SPK yangdisetujuiMengisi identitas laporanMenambahkan satu ataubeberapa petakMenyimpan sebagai drafMengajukan laporanKepala lapangan memeriksaLaporan sesuai?Dikembalikan untuk perbaikanLaporan disetujuiData masuk ke rekapitulasiAdmin mengekspor ExcelSelesaiTidakYa

### 6.3 Alur BBM

\#chatgpt-mermaid-\_r_52l\_{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:16px;fill:rgb(13, 13, 13);}@keyframes edge-animation-frame{from{stroke-dashoffset:0;}}@keyframes dash{to{stroke-dashoffset:0;}}#chatgpt-mermaid-\_r_52l\_ .edge-animation-slow{stroke-dasharray:9,5!important;stroke-dashoffset:900;animation:dash 50s linear infinite;stroke-linecap:round;}#chatgpt-mermaid-\_r_52l\_ .edge-animation-fast{stroke-dasharray:9,5!important;stroke-dashoffset:900;animation:dash 20s linear infinite;stroke-linecap:round;}#chatgpt-mermaid-\_r_52l\_ .error-icon{fill:rgb(243, 243, 243);}#chatgpt-mermaid-\_r_52l\_ .error-text{fill:rgb(13, 13, 13);stroke:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ .edge-thickness-normal{stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ .edge-thickness-thick{stroke-width:3.5px;}#chatgpt-mermaid-\_r_52l\_ .edge-pattern-solid{stroke-dasharray:0;}#chatgpt-mermaid-\_r_52l\_ .edge-thickness-invisible{stroke-width:0;fill:none;}#chatgpt-mermaid-\_r_52l\_ .edge-pattern-dashed{stroke-dasharray:3;}#chatgpt-mermaid-\_r_52l\_ .edge-pattern-dotted{stroke-dasharray:2;}#chatgpt-mermaid-\_r_52l\_ .marker{fill:rgb(143, 143, 143);stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52l\_ .marker.cross{stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52l\_ svg{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:16px;}#chatgpt-mermaid-\_r_52l\_ p{margin:0;}#chatgpt-mermaid-\_r_52l\_ .label{font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ .cluster-label text{fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ .cluster-label span{color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ .cluster-label span p{background-color:transparent;}#chatgpt-mermaid-\_r_52l\_ .label text,#chatgpt-mermaid-\_r_52l\_ span{fill:rgb(13, 13, 13);color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ .node rect,#chatgpt-mermaid-\_r_52l\_ .node circle,#chatgpt-mermaid-\_r_52l\_ .node ellipse,#chatgpt-mermaid-\_r_52l\_ .node polygon,#chatgpt-mermaid-\_r_52l\_ .node path{fill:rgb(222, 234, 251);stroke:rgb(83, 154, 248);stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ .rough-node .label text,#chatgpt-mermaid-\_r_52l\_ .node .label text,#chatgpt-mermaid-\_r_52l\_ .image-shape .label,#chatgpt-mermaid-\_r_52l\_ .icon-shape .label{text-anchor:middle;}#chatgpt-mermaid-\_r_52l\_ .node .katex path{fill:#000;stroke:#000;stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ .rough-node .label,#chatgpt-mermaid-\_r_52l\_ .node .label,#chatgpt-mermaid-\_r_52l\_ .image-shape .label,#chatgpt-mermaid-\_r_52l\_ .icon-shape .label{text-align:center;}#chatgpt-mermaid-\_r_52l\_ .node.clickable{cursor:pointer;}#chatgpt-mermaid-\_r_52l\_ .root .anchor path{fill:rgb(143, 143, 143)!important;stroke-width:0;stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52l\_ .arrowheadPath{fill:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52l\_ .edgePath .path{stroke:rgb(143, 143, 143);stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ .flowchart-link{stroke:rgb(143, 143, 143);fill:none;}#chatgpt-mermaid-\_r_52l\_ .edgeLabel{background-color:rgb(252, 252, 252);text-align:center;}#chatgpt-mermaid-\_r_52l\_ .edgeLabel p{background-color:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_52l\_ .edgeLabel rect{opacity:0.5;background-color:rgb(252, 252, 252);fill:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_52l\_ .labelBkg{background-color:rgba(252, 252, 252, 0.5);}#chatgpt-mermaid-\_r_52l\_ .cluster rect{fill:rgb(243, 243, 243);stroke:rgba(0, 0, 0, 0.1);stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ .cluster text{fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ .cluster span{color:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ div.mermaidTooltip{position:absolute;text-align:center;max-width:200px;padding:2px;font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";font-size:12px;background:rgb(243, 243, 243);border:1px solid rgba(0, 0, 0, 0.1);border-radius:2px;pointer-events:none;z-index:100;}#chatgpt-mermaid-\_r_52l\_ .flowchartTitleText{text-anchor:middle;font-size:18px;fill:rgb(13, 13, 13);}#chatgpt-mermaid-\_r_52l\_ rect.text{fill:none;stroke-width:0;}#chatgpt-mermaid-\_r_52l\_ .icon-shape,#chatgpt-mermaid-\_r_52l\_ .image-shape{background-color:rgb(252, 252, 252);text-align:center;}#chatgpt-mermaid-\_r_52l\_ .icon-shape p,#chatgpt-mermaid-\_r_52l\_ .image-shape p{background-color:rgb(252, 252, 252);padding:2px;}#chatgpt-mermaid-\_r_52l\_ .icon-shape .label rect,#chatgpt-mermaid-\_r_52l\_ .image-shape .label rect{opacity:0.5;background-color:rgb(252, 252, 252);fill:rgb(252, 252, 252);}#chatgpt-mermaid-\_r_52l\_ .label-icon{display:inline-block;height:1em;overflow:visible;vertical-align:-0.125em;}#chatgpt-mermaid-\_r_52l\_ .node .label-icon path{fill:currentColor;stroke:revert;stroke-width:revert;}#chatgpt-mermaid-\_r_52l\_ .node .neo-node{stroke:rgb(83, 154, 248);}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].node rect,#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].cluster rect,#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].node polygon{stroke:url(#chatgpt-mermaid-\_r_52l\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].swimlane.cluster rect{filter:none;}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].node path{stroke:url(#chatgpt-mermaid-\_r_52l\_-gradient);stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].node .outer-path{filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].node .neo-line path{stroke:rgb(83, 154, 248);filter:none;}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].node circle{stroke:url(#chatgpt-mermaid-\_r_52l\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].node circle .state-start{fill:#000000;}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].icon-shape .icon{fill:url(#chatgpt-mermaid-\_r_52l\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52l\_ [data-look="neo"].icon-shape .icon-neo path{stroke:url(#chatgpt-mermaid-\_r_52l\_-gradient);filter:drop-shadow( 1px 2px 2px rgba(185,185,185,1));}#chatgpt-mermaid-\_r_52l\_ .node text{font-size:14px;font-weight:600;letter-spacing:normal;fill:rgb(0, 79, 153);}#chatgpt-mermaid-\_r_52l\_ .edgeLabels text{font-size:13px;font-weight:600;letter-spacing:-0.08px;fill:rgb(0, 79, 153);}#chatgpt-mermaid-\_r_52l\_ .node tspan[font-weight="normal"],#chatgpt-mermaid-\_r_52l\_ .edgeLabels tspan[font-weight="normal"]{font-weight:600;}#chatgpt-mermaid-\_r_52l\_ .edgeLabel .label rect{opacity:1;rx:13px;ry:13px;fill:rgb(245, 250, 255);stroke:rgb(206, 219, 229);stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ .node rect,#chatgpt-mermaid-\_r_52l\_ .node circle,#chatgpt-mermaid-\_r_52l\_ .node ellipse,#chatgpt-mermaid-\_r_52l\_ .node polygon,#chatgpt-mermaid-\_r_52l\_ .node path{fill:rgb(229, 243, 255);stroke:rgba(0, 0, 0, 0.1);stroke-width:1px;}#chatgpt-mermaid-\_r_52l\_ .node rect{rx:16px;ry:16px;}#chatgpt-mermaid-\_r_52l\_ .node.mermaid-decision .label-container{fill:rgb(245, 250, 255);stroke:rgb(206, 219, 229);stroke-dasharray:2,2;}#chatgpt-mermaid-\_r_52l\_ .edgePaths .flowchart-link{stroke:rgb(143, 143, 143);stroke-width:1px;stroke-linecap:round;stroke-linejoin:round;}#chatgpt-mermaid-\_r_52l\_ .marker{fill:rgb(143, 143, 143);stroke:rgb(143, 143, 143);}#chatgpt-mermaid-\_r_52l\_ :root{--mermaid-font-family:-apple-system-body,ui-sans-serif,-apple-system,system-ui,"Segoe UI",Helvetica,"Apple Color Emoji",Arial,sans-serif,"Segoe UI Emoji","Segoe UI Symbol";}MulaiPekerja memilih tim droneInput pengajuan atau transaksiBBMMelampirkan bukti jikadiperlukanTransaksi menunggupemeriksaanAdmin memeriksa transaksiData sesuai?Dikembalikan untuk diperbaikiTransaksi diverifikasiSaldo tim diperbarui sesuaijenis transaksiSelesaiTidakYa

## 7. Rancangan database dan hubungan antarentitas

Database utama bernama `pantrack`. Nama tabel dan kolom menggunakan format teknis yang konsisten, sedangkan label antarmuka dan data yang ditampilkan kepada pengguna menggunakan bahasa Indonesia.

### 7.1 Kelompok tabel

| Kelompok    | Tabel utama                                                   | Tujuan                                |
| ----------- | ------------------------------------------------------------- | ------------------------------------- |
| Pengguna    | `peran`, `pengguna`                                           | Login dan hak akses                   |
| Operasional | `drone`, `tim`, `anggota_tim`, `distrik`, `petak`             | Pengelolaan sumber daya dan wilayah   |
| Pekerjaan   | `jenis_pekerjaan`, `spk`, `detail_spk`                        | Target pekerjaan dan daftar petak SPK |
| Realisasi   | `laporan_realisasi`, `detail_realisasi`, `lampiran_realisasi` | Pencatatan hasil kerja                |
| BBM         | `jenis_bbm`, `transaksi_bbm`, `lampiran_bbm`                  | Transaksi dan stok BBM per tim        |
| Tarif       | `tarif_pekerjaan`                                             | Tarif beserta masa berlakunya         |
| Audit       | `riwayat_aktivitas`                                           | Jejak aktivitas pengguna              |
| Pelaporan   | `ekspor_laporan`                                              | Riwayat ekspor Excel                  |

Berkas SQL yang telah disiapkan sebelumnya menjadi dasar awal struktur ini: Unduh database PANTRACK.

### 7.2 Relasi penting

- Satu peran dapat dimiliki banyak pengguna.
- Satu drone dapat dikaitkan dengan tim operasional sesuai periode penugasannya.
- Satu tim memiliki banyak anggota dan banyak transaksi BBM.
- Satu distrik memiliki banyak petak.
- Satu SPK dapat memiliki banyak detail petak.
- Satu laporan realisasi dapat memiliki banyak detail pekerjaan.
- Satu transaksi BBM dapat memiliki beberapa lampiran.
- Satu pengguna dapat menginput atau memeriksa banyak data.
- Satu jenis pekerjaan dapat memiliki beberapa versi tarif sesuai masa berlaku.

Catatan teknis: rancangan SQL awal masih perlu disempurnakan sebelum produksi, terutama untuk riwayat penugasan drone, validasi periode, pembatasan perubahan data yang sudah disetujui, dan pencatatan perubahan status. Relasi satu drone untuk satu tim aktif harus dijaga agar satu stok BBM tidak terhubung ke tim yang salah.

## 8. Aturan bisnis wajib

Aturan berikut harus diterapkan dalam logika backend, validasi formulir, dan pengujian.

| Kode  | Aturan                                                                                                                                                                              |
| ----- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR-01 | Nomor SPK harus unik sesuai kebijakan penomoran perusahaan.                                                                                                                         |
| BR-02 | SPK yang belum disetujui tidak dapat menjadi dasar realisasi final.                                                                                                                 |
| BR-03 | Kepala lapangan boleh menginput dan menyetujui SPK yang sama, dengan jejak aktivitas tersimpan.                                                                                     |
| BR-04 | Satu formulir realisasi dapat mencakup beberapa petak dan tanggal pekerjaan.                                                                                                        |
| BR-05 | Luas target SPK dan luas realisasi harus disimpan terpisah.                                                                                                                         |
| BR-06 | Helper boleh dicatat sebagai nama bebas tanpa akun pengguna.                                                                                                                        |
| BR-07 | Luas realisasi harus bernilai positif untuk pekerjaan yang benar-benar dilaksanakan; laporan kendala tanpa realisasi dapat memiliki luas nol jika diizinkan oleh aturan perusahaan. |
| BR-08 | Pengajuan BBM tidak menambah saldo stok.                                                                                                                                            |
| BR-09 | Hanya transaksi BBM yang sudah diverifikasi yang memengaruhi stok resmi.                                                                                                            |
| BR-10 | Transaksi BBM harus terkait dengan tim yang bertanggung jawab.                                                                                                                      |
| BR-11 | Saldo tim tidak boleh digabungkan otomatis dengan saldo tim lain.                                                                                                                   |
| BR-12 | Laporan yang disetujui tidak boleh diubah tanpa proses koreksi dan riwayat perubahan.                                                                                               |
| BR-13 | Perubahan tarif tidak boleh mengubah tarif historis yang sudah digunakan dalam laporan sebelumnya.                                                                                  |
| BR-14 | Ekspor laporan harus menggunakan filter periode dan status data yang jelas.                                                                                                         |
| BR-15 | Pengguna hanya boleh melihat dan mengubah data sesuai kewenangannya.                                                                                                                |

## 9. Kebutuhan nonfungsional

Kebutuhan nonfungsional menentukan kualitas sistem, bukan hanya fitur yang tersedia.

- Keamanan: kata sandi di-hash, validasi server-side, proteksi CSRF, dan otorisasi berbasis peran.
- Integritas data: penggunaan foreign key, transaksi database, dan validasi untuk mencegah duplikasi serta saldo yang tidak konsisten.
- Kemudahan penggunaan: formulir dapat digunakan melalui browser komputer maupun ponsel dengan tombol dan teks yang mudah dibaca.
- Kinerja: dashboard dan pencarian harus menggunakan filter serta pagination untuk menghindari pemuatan data terlalu banyak.
- Keandalan: data tersimpan di database terpusat dan tersedia prosedur pencadangan.
- Auditabilitas: aktivitas input, pemeriksaan, persetujuan, koreksi, dan ekspor dapat ditelusuri.
- Pemeliharaan: struktur aplikasi dipisahkan ke dalam model, controller, validasi, layanan bisnis, dan tampilan Blade.
- Privasi: lampiran dan laporan operasional hanya dapat diakses oleh pengguna yang berwenang.

Target kinerja yang terukur, seperti waktu respons maksimum dan ukuran berkas yang diperbolehkan, sebaiknya ditetapkan setelah lingkungan server dan jumlah pengguna diketahui.

## 10. Kriteria penerimaan sistem

Fitur dianggap memenuhi kebutuhan awal jika skenario berikut berhasil diuji.

| ID    | Skenario pengujian                                   | Hasil yang diharapkan                                                       |
| ----- | ---------------------------------------------------- | --------------------------------------------------------------------------- |
| AC-01 | Pekerja login                                        | Hanya fitur sesuai perannya yang dapat diakses                              |
| AC-02 | Pekerja menginput SPK                                | SPK tersimpan dengan status menunggu persetujuan                            |
| AC-03 | Kepala lapangan menginput dan menyetujui SPK sendiri | Sistem mengizinkan dan mencatat kedua aktivitas                             |
| AC-04 | Pekerja membuat laporan berisi beberapa petak        | Semua detail petak tersimpan dalam satu laporan                             |
| AC-05 | Kepala lapangan mengembalikan laporan                | Status berubah dan catatan perbaikan dapat dilihat                          |
| AC-06 | Admin memverifikasi penerimaan BBM                   | Saldo tim bertambah sesuai jumlah aktual yang diverifikasi                  |
| AC-07 | Admin memverifikasi penggunaan BBM                   | Saldo tim berkurang sesuai jumlah penggunaan                                |
| AC-08 | Transaksi BBM masih menunggu pemeriksaan             | Saldo resmi tidak berubah                                                   |
| AC-09 | Dua tim mempunyai stok berbeda                       | Perubahan transaksi tim pertama tidak memengaruhi tim kedua                 |
| AC-10 | Admin mengekspor laporan periode tertentu            | File Excel berisi data sesuai periode, status, dan format yang dipilih      |
| AC-11 | Tarif diperbarui                                     | Laporan historis tetap memakai tarif yang berlaku saat transaksi ditetapkan |
| AC-12 | Pengguna mencoba mengakses fitur tanpa izin          | Sistem menolak akses                                                        |

## 11. Teknologi yang digunakan

| Komponen          | Teknologi                         | Kegunaan                                   |
| ----------------- | --------------------------------- | ------------------------------------------ |
| Backend           | Laravel / PHP                     | Logika aplikasi dan pengelolaan data       |
| Database          | MySQL                             | Penyimpanan data operasional               |
| Frontend          | Blade, HTML, CSS, JavaScript      | Antarmuka website                          |
| Framework CSS     | Bootstrap                         | Membuat tampilan responsif                 |
| Visualisasi       | Chart.js                          | Grafik pada dashboard                      |
| Ekspor Excel      | Laravel Excel atau PhpSpreadsheet | Menghasilkan laporan Excel                 |
| Editor            | Visual Studio Code                | Pengembangan kode                          |
| Pengujian         | PHPUnit/Pest dan pengujian manual | Memastikan fitur berjalan sesuai kebutuhan |
| Pengelolaan versi | Git                               | Mencatat perubahan kode                    |

Versi Laravel dan PHP sebaiknya ditentukan berdasarkan kompatibilitas lingkungan server sebelum proyek dimulai.

## 12. Tahapan pengembangan

Saya menyarankan pengembangan dilakukan secara bertahap supaya fitur utama dapat diuji sebelum modul tambahan dibangun.

Tahap 1 — Fondasi sistem

Prioritas tinggi

Login, hak akses, master data, drone, tim, anggota tim, distrik, petak, dan jenis pekerjaan.

Tahap 2 — SPK dan realisasi

Input SPK, persetujuan SPK, formulir realisasi multi-petak, pemeriksaan kepala lapangan, dan riwayat perubahan.

Tahap 3 — BBM

Pengajuan, penerimaan, penggunaan, lampiran bukti, pemeriksaan admin, dan saldo per tim.

Tahap 4 — Dashboard dan laporan

Rekap To Day dan MTD, monitoring status, filter laporan, dan ekspor Excel sesuai format perusahaan.

Tahap 5 — Pengujian dan penerapan

Pengujian hak akses, perhitungan luas dan BBM, validasi data, pencadangan, serta penerapan ke server.

## 13. Risiko dan langkah pencegahan

| Risiko                                          | Penanganan                                                                                          |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Pekerja memasukkan kode petak yang salah        | Validasi berdasarkan data master dan sediakan mekanisme penambahan petak sesuai hak akses           |
| Realisasi dihitung dua kali                     | Gunakan identitas laporan dan detail transaksi yang unik serta aturan validasi realisasi            |
| Saldo BBM berbeda dari kondisi fisik            | Rekam transaksi aktual, verifikasi admin, dan sediakan penyesuaian stok dengan alasan serta riwayat |
| SPK disetujui tanpa pemeriksaan memadai         | Catat penginput dan penyetuju, serta simpan riwayat keputusan                                       |
| Data berubah setelah disetujui                  | Batasi perubahan dan gunakan proses koreksi yang tercatat                                           |
| Laporan Excel tidak sesuai format perusahaan    | Uji ekspor dengan contoh Excel resmi dan cocokkan hasil per kolom                                   |
| Bukti transaksi hilang atau tidak dapat diakses | Gunakan penyimpanan berkas yang terkelola, validasi unggahan, dan pencadangan                       |
| Pengguna melihat data tim lain tanpa izin       | Terapkan pemeriksaan akses di backend pada setiap permintaan data                                   |

## 14. Hal yang masih perlu dikonfirmasi

PRD ini sudah bisa digunakan sebagai dasar pengembangan awal, tetapi beberapa keputusan bisnis masih perlu dikonfirmasi agar implementasinya tidak salah.

## Konfirmasi kebutuhan operasional

Pilih jawaban yang paling sesuai dengan prosedur perusahaan. Jawaban ini akan membantu menyempurnakan PRD sebelum pengkodean dimulai.

1\. Periode laporan hasil kerja

Satu laporan untuk setiap hari

Satu laporan dapat mencakup beberapa tanggal dalam satu bulan

Keduanya dapat digunakan

2\. Proses BBM

Pengajuan diperiksa admin, kemudian penerimaan aktual dicatat sebagai transaksi terpisah

Pekerja langsung mencatat BBM yang sudah diterima, tanpa modul pengajuan di website

Pengajuan dan penerimaan dicatat dalam satu formulir dengan status berbeda

3\. Pemeriksaan laporan realisasi

Kepala lapangan memeriksa setiap laporan sebelum masuk rekap Excel

Kepala lapangan memeriksa pekerjaan, lalu admin melakukan pemeriksaan akhir sebelum ekspor

4\. Dasar perhitungan nilai pekerjaan

Luas realisasi aktual × tarif yang berlaku

Mengikuti ketentuan pembayaran yang ditetapkan dalam SPK

Belum dipastikan dan perlu mengikuti format resmi perusahaan

Catatan tambahan (opsional)

Perbarui PRD berdasarkan jawaban

## 15. Kesimpulan

PRD ini menetapkan PANTRACK sebagai sistem monitoring administrasi operasional drone agrikultur, dengan fokus pada tiga proses utama: pengelolaan SPK, pencatatan realisasi pekerjaan, dan pengelolaan BBM per tim drone. Dashboard dan ekspor Excel menjadi sarana untuk menyampaikan hasil pencatatan tersebut kepada admin dan atasan.

Sebelum masuk ke tahap pemrograman, prioritas berikutnya adalah menyempurnakan aturan bisnis dan database, kemudian membuat migration, model, relasi, serta otorisasi Laravel berdasarkan PRD yang sudah ditetapkan.