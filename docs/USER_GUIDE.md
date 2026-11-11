# Buku Panduan Pengguna (User Guide) SIMRAN

Panduan ini ditujukan bagi Administrator, Pejabat Pembuat Komitmen (PPK), Bendahara, dan Operator Pengelola Anggaran di lingkungan Divhumas Polri dalam mengoperasikan aplikasi **SIMRAN**.

---

## 1. Akses Masuk (Login)

SIMRAN menyediakan dua metode autentikasi yang fleksibel:

1. **Login Standar (Username & Password)**:
   - Akses halaman login melalui tombol **Masuk Admin** di pojok kanan atas atau URL `/login`.
   - Masukkan Username dan Password Anda.
2. **Login Cepat dengan PIN 6 Digit**:
   - Jika Anda telah mengatur PIN pada profil akun Anda, Anda dapat memilih tab **Masuk dengan PIN**.
   - Masukkan 6 digit angka PIN Anda untuk akses cepat tanpa mengetikkan password panjang.

---

## 2. Navigasi & Berpindah Tahun Anggaran

### A. Switcher Tahun Anggaran (Fiscal Year)
Di bagian header atas layar, terdapat penanda tahun anggaran aktif, misalnya **T.A. 2026 ★**.
- Klik tombol dropdown tahun untuk berpindah ke tahun lain (misalnya **T.A. 2027**).
- Seluruh data yang tampil (pagu, realisasi, grafik, dan RPA) akan secara otomatis disesuaikan dengan tahun anggaran yang dipilih.

### B. Pencarian Cepat (Quick Search ⌘K / Ctrl+K)
- Tekan kombinasi tombol **`Ctrl + K`** (atau **`Cmd + K`** di macOS) untuk membuka kotak pencarian global.
- Ketik nama kegiatan, kode akun belanja (misal: `521211`), atau kata kunci regulasi SBM untuk menemukan informasi secara instan.

### C. Ganti Tema Tampilan (Light / Dark Mode)
- Klik tombol **Tema (🌙/☀️)** di header untuk berpindah antara mode terang dan mode gelap yang nyaman untuk mata saat bekerja di malam hari.

---

## 3. Modul Dashboard Utama

Dashboard menyajikan gambaran eksekutif secara komprehensif:

1. **Kartu Ringkasan Metrik**:
   - **Total Pagu DIPA**: Pagu definitif tahun anggaran aktif.
   - **Total Realisasi Riil**: Nominal anggaran yang telah terserap beserta persentase terhadap pagu.
   - **Sisa Anggaran**: Saldo pagu yang belum terserap.
   - **Nilai Capaian IKPA**: Skor kinerja pelaksanaan anggaran terkini.
2. **Grafik Tren Realisasi Kumulatif**:
   - Menampilkan perbandingan garis: Realisasi Riil (Hijau), Target Serapan Triwulanan (Biru), dan Rencana Penarikan Dana / RPA (Kuning).
3. **Komposisi Belanja**:
   - Diagram donat yang memperlihatkan proporsi alokasi Belanja Pegawai (51), Belanja Barang (52), dan Belanja Modal (53).
4. **Top 5 Kegiatan Serapan Tertinggi & Alert Serapan Rendah**:
   - Memberikan peringatan dini bagi kegiatan-kegiatan yang serapannya masih jauh di bawah target bulan berjalan.

---

## 4. Modul Kegiatan & Uraian Kegiatan

### Melihat Daftar Kegiatan
1. Buka menu **Kegiatan**.
2. Anda dapat melihat daftar seluruh program dan kegiatan DIPA, lengkap dengan kode kegiatan, alokasi pagu, nominal serapan, dan persentase capaian.

### Mengelola Uraian Kegiatan
1. Pada salah satu baris kegiatan, klik tombol **Uraian**.
2. Halaman ini menampilkan rincian sub-kegiatan, target output, indikator kinerja, serta rincian kebutuhan anggaran per sub-kegiatan.
3. Administrator dapat menambahkan uraian baru atau memperbarui progres fisik kegiatan.

---

## 5. Modul RPA (Rencana Penarikan Dana)

Modul RPA dirancang dengan antarmuka bergaya **Spreadsheet Excel** untuk memudahkan pemantauan rencana kas:

1. **Melihat Matriks RPA**:
   - Buka menu **Admin → Rencana Penarikan Dana (RPA)**.
   - Tabel menampilkan pembagian rencana penarikan dana dari bulan Januari hingga Desember per item kegiatan dan sumber dana.
2. **Impor Data RPA dari File Excel**:
   - Klik tombol **Impor Excel RPA**.
   - Pilih berkas `.xlsx` hasil unduhan Rencana Penarikan Dana dari aplikasi SAKTI.
   - Sistem akan secara otomatis memetakan kolom dan mengalkulasi total rencana bulanan.
   - Periksa ringkasan pada layar *Preview*, kemudian klik **Simpan Data**.

---

## 6. Modul Standar Biaya Masukan (SBM)

Modul SBM berfungsi sebagai acuan resmi standar tarif biaya perjalanan dinas, honorarium, dan pengadaan:

1. Buka menu **Standar Biaya (SBM)**.
2. Pilih regulasi yang ingin ditinjau:
   - **PMK No. 54 Tahun 2026**
   - **PMK No. 32 Tahun 2025**
   - **PMK No. 39 Tahun 2024**
3. Masukkan kata kunci pencarian, misalnya: *"Uang Harian DKI Jakarta"*, *"Honorarium Narasumber"*, atau *"Tiket Pesawat"*.
4. Sistem akan menampilkan rincian tarif batas tertinggi yang diperkenankan oleh ketentuan Kemenkeu.

---

## 7. Pengaturan Sistem & Manajemen Pengguna

*(Hanya dapat diakses oleh akun berstatus Administrator)*

1. **Manajemen Pengguna**:
   - Buka menu **Pengaturan → Pengguna**.
   - Anda dapat menambah user baru, mengatur level hak akses (*Role*), mereset kata sandi, dan menyetel PIN 6 digit untuk login cepat.
2. **Pengaturan Pagu & Target Serapan**:
   - Masuk ke menu **Pengaturan → Pagu Anggaran**.
   - Sesuaikan target serapan per triwulan (Triwulan I, II, III, dan IV) sesuai target DIPA kementerian/lembaga.
