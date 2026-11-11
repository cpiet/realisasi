# Arsitektur Sistem SIMRAN

Dokumen ini menjelaskan struktur arsitektur, pola perancangan perangkat lunak, skema database, dan alur aliran data (*data flow*) pada aplikasi **SIMRAN (Sistem Monitoring Realisasi Anggaran & IKPA)**.

---

## 1. Ikhtisar Arsitektur

SIMRAN dibangun dengan menerapkan pola arsitektur **Model-View-Controller (MVC)** yang disediakan oleh framework **CodeIgniter 4**, dengan penambahan lapisan **Services** untuk pemrosesan bisnis yang kompleks (seperti penguraian berkas Excel RPA dan PDF IKPA).

```
                      +-----------------------------+
                      |   Browser / Klien Klien     |
                      +--------------+--------------+
                                     | HTTP / HTTPS
                                     v
                      +-----------------------------+
                      |   Web Server / public/      |
                      |   (Apache / Nginx / FPM)    |
                      +--------------+--------------+
                                     | index.php
                                     v
                      +-----------------------------+
                      |   CI4 Filters (Middleware)  |
                      |  - AuthFilter               |
                      |  - RoleFilter               |
                      |  - FiscalYearFilter         |
                      +--------------+--------------+
                                     | Dispatched
                                     v
                      +-----------------------------+
                      |         Controllers         |
                      |  - Dashboard, Activities    |
                      |  - Admin/Rpa, Admin/Ikpa    |
                      |  - Auth, Sbm, Settings      |
                      +------+---------------+------+
                             |               |
              +--------------+               +---------------+
              v                                              v
  +-----------------------+                      +-----------------------+
  |    Business Services  |                      |     Models / Data     |
  |  - RpaExcelParser     |                      |  - ActivityModel      |
  |  - IkpaPdfParser      |                      |  - RpaItemModel       |
  |  - LraImportService   |                      |  - UserModel          |
  +-----------+-----------+                      +-----------+-----------+
              |                                              |
              +----------------------+-----------------------+
                                     | PDO / Query Builder
                                     v
                      +-----------------------------+
                      |   Database Engine (SQLite3) |
                      |   writable/database/        |
                      |   - simran.sqlite           |
                      |   - sbm.sqlite              |
                      +-----------------------------+
```

---

## 2. Lapisan Inti Aplikasi

### A. Routing & Filters (Middleware)
- **`app/Config/Routes.php`**: Mendefinisikan seluruh endpoint publik dan grup rute administrator (`admin/*`).
- **`app/Filters/AuthFilter.php`**: Memvalidasi status sesi pengguna yang login. Mencegah akses tanpa autentikasi ke halaman privat.
- **`app/Filters/RoleFilter.php`**: Menerapkan kontrol akses berbasis peran (*Role-Based Access Control / RBAC*) untuk membatasi aksi tertentu (seperti impor berkas atau kelola pengguna).
- **`app/Filters/FiscalYearFilter.php`**: Membaca tahun anggaran aktif dari URL query (`?year=2026`) atau session. Jika tidak disediakan, filter secara otomatis menetapkan tahun anggaran saat ini sehingga data yang ditampilkan konsisten.

### B. Controllers
- **`Dashboard.php`**: Menghitung metrik eksekutif, data akumulatif bulanan realisasi riil, target penyerapan, dan payload visualisasi Chart.js.
- **`Activities.php`**: Menangani daftar kegiatan DIPA, pagu per kegiatan, dan progres serapan.
- **`ActivityDescriptions.php`**: Menangani pencatatan uraian dan detail sub-kegiatan.
- **`Admin/Rpa.php`**: Mengatur data rencana penarikan dana bulanan, upload dan parsing file Excel RPA, serta kalkulasi per komponen belanja.
- **`Admin/Ikpa.php`**: Mengatur data histori capaian IKPA bulanan dan parsing PDF IKPA dari OM-SPAN / SAKTI.
- **`Sbm.php`**: Menyajikan katalog dan pencarian tarif regulasi Standar Biaya Masukan (PMK 39/2024, 32/2025, 54/2026).

### C. Services (Business Logic)
- **`RpaExcelParser.php`**: Mengurai lembar kerja Microsoft Excel format RPA DIPA, mendeteksi header secara dinamis, mengekstrak nilai rencana per bulan (Januari - Desember), serta melakukan validasi integritas total nominal.
- **`IkpaPdfParser.php`**: Melakukan ekstraksi teks pada dokumen laporan capaian IKPA berformat PDF.
- **`LraImportService.php`**: Memproses impor Laporan Realisasi Anggaran (LRA).

---

## 3. Desain & Skema Database

Database utama disimpan dalam format SQLite pada berkas `writable/database/simran.sqlite`. Format file ini memungkinkan portabilitas tinggi, pencadangan instan cukup dengan menyalin file, serta kinerja pembacaan yang sangat cepat tanpa memerlukan daemon database terpisah.

### Tabel Utama:

#### 1. `users`
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | INTEGER PRIMARY KEY | Identifier unik pengguna |
| `username` | VARCHAR(100) | Username untuk autentikasi |
| `password_hash` | VARCHAR(255) | Hash password menggunakan bcrypt |
| `pin` | VARCHAR(6) | PIN 6 digit untuk login cepat |
| `role` | VARCHAR(50) | Peran pengguna (`admin`, `superadmin`, `operator`, `viewer`) |
| `login_token` | VARCHAR(255) | Token remember-me sesi |
| `created_at` / `updated_at` | DATETIME | Jejak waktu perubahan |

#### 2. `fiscal_years`
| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | INTEGER PRIMARY KEY | Identifier unik |
| `year` | INTEGER | Tahun anggaran (misal: 2026, 2027) |
| `is_active` | BOOLEAN | Menandai tahun aktif |
| `description` | TEXT | Keterangan tahun anggaran |

#### 3. `programs` & `activities`
- Menyimpan hirarki Program, Kegiatan, dan Akun Belanja sesuai DIPA Divhumas Polri.
- Terhubung dengan `fiscal_years` untuk memastikan pemisahan data antar tahun anggaran.

#### 4. `rpa_items` & `rpa_details`
- Menyimpan alokasi target penarikan dana per bulan (`jan`, `feb`, `mar`, ... `des`).
- Menyimpan metadata sumber dana (Rupiah Murni / PNBP), jenis belanja, dan revisi DIPA.

#### 5. `realizations`
- Menyimpan data transaksi realisasi belanja riil yang diimpor dari SP2D / LRA SAKTI.

---

## 4. Keamanan & Sanitasi

1. **Prepared Statements**: Seluruh interaksi database menggunakan Query Builder CodeIgniter 4 dengan parameter binding untuk mencegah SQL Injection.
2. **CSRF Protection**: Token CSRF diinjeksikan ke dalam setiap formulir HTML dan diperiksa pada setiap permintaan POST/PUT/DELETE.
3. **Password & PIN Hashing**: Menggunakan fungsi native PHP `password_hash()` dengan algoritma standard industri.
4. **Proteksi File Writable**: Direktori `writable/` diproteksi dari akses langsung peramban menggunakan konfigurasi web server (.htaccess / Nginx rule).

