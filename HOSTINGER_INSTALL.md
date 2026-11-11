# Instalasi SIMRAN di Hostinger

Paket ini dapat dipasang pada domain utama, subdomain, atau subfolder. Alamat dasar dideteksi otomatis, sehingga tidak perlu mengubah URL di dalam kode.

## Tujuan pemasangan saat ini

`https://lutfialfandra.my.id/realisasi`

## Langkah pemasangan

1. Buka **File Manager** Hostinger dan masuk ke `public_html`.
2. Unggah arsip `simran-hostinger-realisasi.zip`.
3. Ekstrak sehingga struktur akhirnya menjadi `public_html/realisasi/app`, `public_html/realisasi/public`, `public_html/realisasi/vendor`, dan seterusnya. Jangan sampai terbentuk folder ganda `realisasi/realisasi`.
4. Pastikan versi PHP domain minimal **PHP 8.2**.
5. Aktifkan ekstensi PHP: `intl`, `mbstring`, `sqlite3`, `pdo_sqlite`, `zip`, `gd`, dan `fileinfo` jika tersedia di menu PHP Extensions.
6. Atur permission folder `writable` beserta seluruh isinya ke `775`. Jika hosting tidak menerima `775`, gunakan `755`; database dan folder session harus tetap dapat ditulis PHP.
7. Buka `https://lutfialfandra.my.id/realisasi`.
8. Login awal dengan `admin` / `admin123`, lalu segera buka **Pengaturan → Keamanan Administrator** dan ganti password.

## Pemeriksaan setelah upload

- Dashboard dan grafik tampil tanpa aset yang hilang.
- Login mengarah kembali ke `/realisasi`, bukan ke root domain.
- Menu Kegiatan dapat dibuka dan detail muncul.
- Import Excel dapat mencapai halaman preview.
- Menu Pengaturan dapat mengunduh backup SQLite.

## Catatan database

Database berada di `writable/database/simran.sqlite` dan sudah disertakan. Lakukan backup dari menu Pengaturan sebelum mengimpor data besar. Jangan menghapus folder `writable` ketika memperbarui aplikasi karena folder tersebut menyimpan database, session, cache, dan file unggahan sementara.

## Jika muncul Error 500

- Pastikan PHP 8.2 atau lebih baru aktif.
- Pastikan ekstensi PHP pada langkah 5 aktif.
- Pastikan `mod_rewrite` tersedia (umumnya sudah aktif di Hostinger).
- Pastikan folder `writable` dapat ditulis.
- Periksa `writable/logs` untuk pesan error terbaru.
