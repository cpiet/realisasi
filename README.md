# SIMRAN — Sistem Monitoring Realisasi Anggaran & IKPA

<div align="center">

![CodeIgniter 4](https://img.shields.io/badge/CodeIgniter-4.5.5-orange.svg?style=flat-square&logo=codeigniter)
![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-blue.svg?style=flat-square&logo=php)
![SQLite](https://img.shields.io/badge/Database-SQLite3-003B57.svg?style=flat-square&logo=sqlite)
![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205.3-purple.svg?style=flat-square&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)
![Status](https://img.shields.io/badge/Release-v1.0%20(Production)-success.svg?style=flat-square)

**Aplikasi Monitoring Realisasi Anggaran, Rencana Penarikan Dana (RPA), Indikator Kinerja Pelaksanaan Anggaran (IKPA), dan Standar Biaya Masukan (SBM) untuk Divisi Hubungan Masyarakat (Divhumas) Polri.**

[Live Production](https://lutfialfandra.my.id/realisasi/) · [Dokumentasi Lengkap](docs/) · [Panduan Instalasi](#-instalasi--setup-lokal)

</div>

---

## 📑 Daftar Isi

- [Tentang SIMRAN](#-tentang-simran)
- [Fitur Utama](#-fitur-utama)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Struktur Direktori](#-struktur-direktori)
- [Instalasi & Setup Lokal](#-instalasi--setup-lokal)
- [Panduan Deployment (Hostinger / cPanel / VPS)](#-panduan-deployment)
- [Struktur Database & Migrasi](#-struktur-database--migrasi)
- [Keamanan & Akun Default](#-keamanan--akun-default)
- [Dokumentasi Tambahan](#-dokumentasi-tambahan)
- [Lisensi](#-lisensi)

---

## 💡 Tentang SIMRAN

**SIMRAN** (*Sistem Monitoring Realisasi Anggaran*) adalah platform web berbasis PHP (CodeIgniter 4) yang dirancang khusus untuk mempermudah monitoring, analisis serapan anggaran, pengelolaan Rencana Penarikan Dana (RPA), evaluasi target IKPA, dan referensi Standar Biaya Masukan (SBM) PMK Kemenkeu secara akurat, cepat, dan transparan.

Aplikasi ini telah dioptimalkan untuk performa tinggi dengan arsitektur **Zero-Configuration Base URL** (dapat berjalan di root domain, subdomain, maupun subfolder tanpa penyesuaian kode) dan database mandiri berbasis **SQLite3** yang ringan serta mudah dicadangkan.

---

## ✨ Fitur Utama

### 1. 📊 Dashboard Eksekutif & Visualisasi Realisasi
- **Grafik Interaktif Chart.js**: Visualisasi tren kumulatif realisasi anggaran riil vs target serapan vs RPA per bulan.
- **Komposisi Belanja**: Pemecahan alokasi Belanja Pegawai (51), Belanja Barang (52), dan Belanja Modal (53).
- **Executive Metric Cards**: Ringkasan Pagu DIPA, Total Realisasi (Nominal & Persentase), Sisa Anggaran, dan Skor IKPA.
- **Top Activities & Alert**: Pemantauan 5 kegiatan dengan serapan tertinggi dan deteksi dini kegiatan yang tertinggal dari target.

### 2. 📅 Dukungan Multi Tahun Anggaran (Fiscal Year Switcher)
- Penggantian Tahun Anggaran (T.A. 2026, 2027, dst.) secara instan dari header navbar melalui endpoint `set-year/(:num)`.
- Filter global otomatis (*middleware*) yang menjaga konteks tahun aktif pada seluruh modul (Dashboard, Kegiatan, RPA, IKPA, dan Pengaturan).

### 3. 📑 Manajemen Rencana Penarikan Dana (RPA) & Tampilan Spreadsheet
- **Spreadsheet Styled UI**: Tampilan matriks tabel RPA bulanan (Januari - Desember) yang ringkas, bersih, dan mudah dibaca.
- **Excel Import Parser**: Mendukung upload berkas Excel RPA (format dinamis) dengan deteksi kolom cerdas.
- **Detail Rincian RPA**: Monitoring sumber dana (RM/PNBP/BLU), komponen belanja, dan revisi berkala.

### 4. 📝 Modul Uraian Kegiatan
- Pencatatan sub-kegiatan dan uraian detail pelaksanaan kegiatan.
- Tracking progres output, sasaran kegiatan, dan rincian alokasi anggaran per sub-kegiatan.

### 5. 🎯 Evaluasi Target & Kinerja IKPA
- Monitoring nilai deviasi halaman III DIPA, penyerapan anggaran, dan data transaksi.
- Input realisasi IKPA bulanan dan visualisasi grafik tren nilai kinerja.

### 6. 📚 Standar Biaya Masukan (SBM) Terintegrasi
- Pencarian referensi regulasi resmi PMK Kemenkeu:
  - **PMK No. 39/2024**
  - **PMK No. 32/2025**
  - **PMK No. 54/2026**
- Fitur pencarian cepat (*fuzzy search*) tarif uang harian dinas, honorarium, tiket perjalanan, dan uang representasi.

### 7. 🔐 Keamanan & Autentikasi Pengguna
- **Login PIN 6 Digit**: Alternatif login cepat dan aman dengan kombinasi PIN angka.
- **Manajemen User Admin**: Tambah, ubah peran (*superadmin*, *admin*, *operator*, *viewer*), aktifkan/nonaktifkan akun, serta reset PIN.
- **Proteksi Session & CSRF**: Dilengkapi enkripsi token session dan proteksi CSRF di seluruh form request.

### 8. 🎨 UI Modern, Responsif & Dark Mode
- Mengadopsi desain modern ala *macOS Sonoma / Apple Human Interface Guidelines*.
- **Quick Search Pill (⌘K / Ctrl+K)**: Pintasan keyboard untuk pencarian instan kegiatan, akun, atau regulasi.
- **Offline First Assets**: Library CSS & JS (Bootstrap 5.3, Chart.js) tersimpan lokal dalam folder `public/`, sehingga tetap cepat dan tidak bergantung pada CDN publik.

---

## 🛠 Teknologi yang Digunakan

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Backend Framework** | [CodeIgniter 4.5.5](https://codeigniter.com/) | MVC PHP Framework berperforma tinggi |
| **Bahasa Pemrograman**| PHP 8.2 / 8.3 / 8.4 | Menggunakan fitur PHP modern (typed properties, match) |
| **Database** | SQLite 3 | Serverless, zero-config, portable file database |
| **Spreadsheet Engine** | [PhpSpreadsheet 2.x](https://phpspreadsheet.readthedocs.io/) | Parsing & Export file Excel (.xlsx, .xls) |
| **PDF Parsing Engine** | Smalot / PdfParser | Ekstraksi otomatis data laporan keuangan berformat PDF |
| **Frontend Style** | Bootstrap 5.3.3 + Custom CSS | Responsif untuk desktop, tablet, dan smartphone |
| **Icons** | Bootstrap Icons 1.11 | Ikon web berbasis font vektor |
| **Charts** | Chart.js 4.4 | Grafik dinamis & responsif |

---

## 📁 Struktur Direktori

```text
realisasi/
├── app/
│   ├── Config/              # Konfigurasi sistem, routing, filters, database
│   ├── Controllers/         # Controller utama & modul Admin
│   │   ├── Admin/           # Controller RPA, IKPA, Imports, Settings, Users
│   │   ├── Activities.php   # Manajemen Kegiatan
│   │   ├── ActivityDescriptions.php # Modul Uraian Kegiatan
│   │   ├── Auth.php         # Login, Logout, Autentikasi PIN
│   │   ├── Dashboard.php    # Halaman Utama Dashboard & Metrics
│   │   └── Sbm.php          # Modul Standar Biaya Masukan
│   ├── Database/            # Skrip Migrasi & Seeder
│   │   ├── Migrations/      # Migrasi tabel, kolom PIN 6 digit, token
│   │   └── Seeds/           # Seeder data awal SIMRAN
│   ├── Filters/             # Filter autentikasi, role, dan FiscalYearFilter
│   ├── Libraries/           # Helper data JSON SBM (PMK 39, 32, 54)
│   ├── Models/              # Model data CodeIgniter 4
│   ├── Services/            # Parser Excel RPA, Parser PDF IKPA, LRA Service
│   └── Views/               # Tampilan UI Blade-like PHP
├── docs/                    # Dokumentasi lengkap sistem
│   ├── ARCHITECTURE.md      # Arsitektur sistem & skema data
│   ├── DEPLOYMENT.md        # Panduan deployment hosting & server
│   ├── USER_GUIDE.md        # Panduan penggunaan lengkap
│   └── API_AND_FEATURES.md  # Referensi endpoint & fitur teknis
├── public/                  # Document Root web server (index.php, css, js)
│   ├── css/                 # simran.css, bootstrap.min.css, sbm.css
│   └── js/                  # bootstrap.bundle, chart.umd, simran-dashboard
├── writable/                # File yang dapat ditulis aplikasi
│   ├── cache/               # Cache aplikasi
│   ├── database/            # Lokasi file simran.sqlite & sbm.sqlite
│   ├── logs/                # Log error CodeIgniter
│   └── session/             # Session file handler
├── .env.example             # Template konfigurasi environment
├── .htaccess                # Konfigurasi rewrite Apache
├── composer.json            # Daftar dependensi paket PHP
├── HOSTINGER_INSTALL.md     # Ringkasan cepat pemasangan di Hostinger
└── README.md                # Dokumentasi utama proyek
```

---

## 💻 Instalasi & Setup Lokal

### Prasyarat
1. **PHP**: Versi **8.2** atau lebih tinggi (rekomendasi PHP 8.3/8.4).
2. **Ekstensi PHP Aktif**:
   * `pdo_sqlite` & `sqlite3`
   * `intl`
   * `mbstring`
   * `gd`
   * `zip`
   * `fileinfo`
3. **Web Server**: Apache / Nginx / Laragon / PHP Built-in Server.
4. **Composer**: Versi 2.x.

### Langkah-langkah:

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/cpiet/realisasi.git
   cd realisasi
   ```

2. **Install Dependensi Composer**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Konfigurasi Environment**:
   Salin `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
   *Secara default, URL aplikasi akan otomatis mendeteksi alamat host Anda.*

4. **Inisialisasi Database**:
   Jalankan migrasi dan seeder untuk membuat tabel dan akun administrator awal:
   ```bash
   php spark migrate
   php spark db:seed SimranSeeder
   ```

5. **Jalankan Web Server Lokal**:
   * Jika menggunakan **Laragon**: letakkan folder di `d:/laragon/www/realisasi` dan buka `http://realisasi.test/` atau `http://localhost/realisasi/public`.
   * Atau menggunakan perintah internal CodeIgniter:
     ```bash
     php spark serve
     ```
     Buka peramban di `http://localhost:8080`.

---

## 🌐 Panduan Deployment

SIMRAN dirancang agar sangat mudah dipasang pada berbagai jenis hosting (Hostinger, cPanel, Plesk, atau VPS Linux).

Detail panduan lengkap dapat dilihat di [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

### Langkah Cepat Deployment di Hostinger / cPanel:
1. Pastikan versi PHP domain disetel minimal **PHP 8.2**.
2. Unggah file proyek ke dalam folder tujuan (misal: `public_html/realisasi/`).
3. Berikan hak akses tulis (*permission*) pada folder `writable/` beserta sub-foldernya:
   ```bash
   chmod -R 775 writable/
   ```
4. Pastikan `mod_rewrite` aktif agar rute URL bersih (*clean URL*) berjalan lancar.
5. Akses aplikasi melalui URL domain Anda: `https://domainanda.com/realisasi/`.

---

## 🗄 Struktur Database & Migrasi

SIMRAN menggunakan engine **SQLite 3** yang tersimpan di `writable/database/simran.sqlite`.

Tabel-tabel utama:
- `users`: Data pengguna, peran (*role*), status aktif, password hash, PIN 6 digit, dan remember token.
- `fiscal_years`: Daftar tahun anggaran aktif dan status default.
- `programs` & `activities`: Master struktur program dan kegiatan DIPA.
- `activity_descriptions`: Rincian uraian sub-kegiatan.
- `rpa_items` & `rpa_details`: Perencanaan penarikan dana bulanan (Januari - Desember).
- `realizations`: Pencatatan realisasi belanja riil per akun dan tanggal.
- `ikpa_periods` & `ikpa_scores`: Nilai indikator kinerja per periode.
- `sbm_entries`: Database referensi Standar Biaya Masukan.

---

## 🔒 Keamanan & Akun Default

Setelah instalasi pertama kali melalui seeder, akun default yang tersedia adalah:

* **Username**: `admin`
* **Password**: `admin123`
* **PIN Default**: `123456`

> [!IMPORTANT]
> **Segera ganti password dan PIN default** setelah login pertama kali melalui menu **Pengaturan → Akun Pengguna** untuk menjaga keamanan data anggaran Anda.

---

## 📚 Dokumentasi Tambahan

Untuk panduan mendalam, silakan baca dokumentasi di folder `docs/`:

* 🏛 [**Arsitektur Sistem & Desain Database**](docs/ARCHITECTURE.md)
* 🚀 [**Panduan Deployment Produksi**](docs/DEPLOYMENT.md)
* 📖 [**Buku Panduan Pengguna (User Guide)**](docs/USER_GUIDE.md)
* 🔌 [**Spesifikasi API & Fitur Teknis**](docs/API_AND_FEATURES.md)

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah lisensi [MIT License](LICENSE). Bebas digunakan, dimodifikasi, dan didistribusikan untuk kepentingan dinas dan pengembangan instansi.

<div align="center">
  <sub>Dikembangkan dengan dedikasi untuk optimalisasi transparansi &amp; akuntabilitas anggaran Divhumas Polri.</sub>
</div>
