# Spesifikasi API & Fitur Teknis SIMRAN

Dokumen ini mendokumentasikan rute URL, endpoint kontroler, filter middleware, dan parameter teknis yang digunakan di dalam aplikasi **SIMRAN**.

---

## 1. Daftar Rute Aplikasi (`app/Config/Routes.php`)

### Rute Publik & Pengunjung
| Method | Rute | Handler | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | `Dashboard::index` | Halaman utama dashboard monitoring |
| `GET` | `/set-year/(:num)` | `Dashboard::switchYear/$1` | Endpoint untuk mengganti tahun anggaran aktif dan redirect kembali |
| `GET` | `/activities` | `Activities::index` | Halaman daftar kegiatan dan serapan |
| `GET` | `/activities/(:segment)` | `Activities::detail/$1` | Halaman detail kegiatan |
| `GET` | `/activities/(:segment)/uraian` | `ActivityDescriptions::index/$1` | Daftar uraian sub-kegiatan |
| `GET` | `/sbm` | `Sbm::index` | Katalog referensi Standar Biaya Masukan (SBM) |
| `GET` | `/sbm/(:segment)` | `Sbm::item/$1` | Rincian item tabel tarif SBM tertentu |
| `GET` | `/sbm/search` | `Sbm::search` | Endpoint AJAX pencarian cepat tarif SBM |

### Rute Autentikasi (`Auth`)
| Method | Rute | Handler | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/login` | `Auth::login` | Tampilan formulir login (Username & PIN) |
| `POST` | `/login` | `Auth::attemptLogin` | Pemrosesan autentikasi login username & password |
| `POST` | `/login/pin` | `Auth::attemptPinLogin` | Pemrosesan autentikasi login dengan PIN 6 digit |
| `GET` | `/logout` | `Auth::logout` | Penghapusan sesi pengguna |

### Rute Khusus Administrator (`admin/*`)
*Seluruh rute dalam grup ini dilindungi oleh filter `auth` dan `role:admin,superadmin`.*

| Method | Rute | Handler | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/rpa` | `Admin\Rpa::index` | Tampilan matriks tabel RPA bulanan |
| `GET` | `/admin/rpa/detail/(:num)` | `Admin\Rpa::detail/$1` | Rincian RPA per kegiatan |
| `POST` | `/admin/rpa/import` | `Admin\Rpa::importExcel` | Upload & eksekusi impor file Excel RPA |
| `GET` | `/admin/ikpa` | `Admin\Ikpa::index` | Pengelolaan data dan grafik IKPA |
| `POST` | `/admin/ikpa/import-pdf` | `Admin\Ikpa::importPdf` | Ekstraksi dan simpan capaian IKPA dari PDF |
| `GET` | `/admin/realizations` | `Admin\Realizations::index` | Pengelolaan data transaksi realisasi belanja |
| `GET` | `/admin/imports` | `Admin\Imports::index` | Halaman upload berkas LRA / SP2D |
| `GET` | `/admin/users` | `Admin\Users::index` | Manajemen pengguna & setelan PIN 6 digit |
| `POST` | `/admin/users/create` | `Admin\Users::create` | Tambah pengguna baru |
| `POST` | `/admin/users/update/(:num)`| `Admin\Users::update/$1` | Perbarui data / role pengguna |
| `POST` | `/admin/users/set-pin/(:num)`| `Admin\Users::setPin/$1` | Setel / reset PIN 6 digit pengguna |
| `GET` | `/admin/settings/pagu` | `Admin\Settings::pagu` | Konfigurasi target serapan per triwulan |

---

## 2. Filter Middleware (`app/Filters/`)

### `FiscalYearFilter`
- **Fungsi**: Membaca parameter query `?year=XXXX` atau nilai session `active_fiscal_year`.
- **Mekanisme**:
  ```php
  // Mencari tahun yang diminta pengguna, jika valid simpan ke session
  $year = $request->getGet('year');
  if ($year && is_numeric($year)) {
      session()->set('active_fiscal_year', (int)$year);
  }
  ```
- **Dampak**: Memastikan seluruh model dan controller secara konsisten mengeksekusi kueri `WHERE fiscal_year = ...` sesuai preferensi pengguna.

### `AuthFilter`
- Memeriksa apakah session `logged_in` bernilai `true`. Jika pengguna belum login dan mencoba mengakses rute terproteksi, mereka akan diarahkan ke `/login` dengan menyimpan URL target awal (*redirect back*).

---

## 3. Format Impor Berkas

### A. Template Berkas Excel RPA
Untuk proses impor data RPA, berkas Excel yang diunggah harus memiliki format standar penarikan dana bulanan dengan kolom:
- Kode Akun / Komponen Belanja
- Deskripsi Kegiatan / Output
- Alokasi Pagu Total
- Kolom Rencana Bulan: `Januari`, `Februari`, `Maret`, ..., `Desember`

### B. Berkas Laporan Capaian IKPA (PDF)
Aplikasi mendukung dokumen PDF resmi yang diunduh langsung dari aplikasi OM-SPAN / SAKTI Kementerian Keuangan. Parser secara otomatis mengekstrak tabel komponen nilai:
- Revisi DIPA
- Deviasi Halaman III DIPA
- Penyerapan Anggaran
- Belanja Kontraktual
- Penyelesaian Tagihan
- Pengelolaan UP dan TUP
- Dispensasi SPM

