# Panduan Deployment SIMRAN

Dokumen ini memandu proses pemasangan aplikasi **SIMRAN** di lingkungan produksi, baik pada Shared Hosting (Hostinger, cPanel), mau pun Cloud/VPS Linux.

---

## 1. Persyaratan Server

- **Web Server**: Apache 2.4+ (dengan modul `mod_rewrite` aktif) atau Nginx 1.20+.
- **PHP**: Versi **8.2**, **8.3**, atau **8.4**.
- **Ekstensi PHP Wajib**:
  - `sqlite3` & `pdo_sqlite`
  - `intl`
  - `mbstring`
  - `zip`
  - `gd`
  - `fileinfo`
  - `curl`
- **Memory Limit**: Minimal 128 MB (disarankan 256 MB untuk parsing Excel berukuran besar).

---

## 2. Deployment pada Hostinger / Shared Hosting cPanel

SIMRAN telah dirancang khusus dengan fitur **Zero-Config Base URL**, sehingga dapat langsung dipasang pada:
- **Domain Utama**: `https://contoh.com/`
- **Subdomain**: `https://simran.contoh.com/`
- **Subfolder**: `https://contoh.com/realisasi/`

### Langkah-langkah:

1. **Persiapan Arsip ZIP**:
   Di komputer lokal, siapkan arsip ZIP yang memuat seluruh file proyek (`app`, `public`, `vendor`, `writable`, `.env`, `.htaccess`, `composer.json`, dsb.).
   *(Pastikan tidak menyertakan folder `.git`, `.agents`, atau backup lokal).*

2. **Upload ke File Manager**:
   - Buka File Manager Hostinger / cPanel.
   - Masuk ke direktori target:
     - Jika subfolder: `public_html/realisasi/`
     - Jika domain utama: `public_html/`
   - Unggah berkas ZIP dan ekstrak.

3. **Konfigurasi Hak Akses (Permissions)**:
   Aplikasi membutuhkan izin menulis pada folder `writable/` untuk menyimpan log, sesi, dan database SQLite:
   ```bash
   chmod -R 775 writable/
   ```
   *Catatan: Jika server Anda tidak mengizinkan permission 775, gunakan 755. Pastikan user web server (apache/nobody) dapat menulis ke folder tersebut.*

4. **Pengaturan Versi PHP**:
   - Di panel hosting, buka menu **PHP Configuration**.
   - Pilih versi **PHP 8.2** atau lebih baru.
   - Di tab **PHP Extensions**, pastikan `sqlite3`, `pdo_sqlite`, `intl`, `mbstring`, `zip`, `gd`, dan `fileinfo` dalam keadaan tercentang (aktif).

5. **Pengaturan File `.htaccess`**:
   Pastikan file `.htaccess` di root proyek berisi:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^(.*)$ public/$1 [L]
   </IfModule>
   ```
   *File ini secara otomatis meneruskan seluruh request ke folder `public/` tanpa perlu memindahkan file keluar dari struktur proyek.*

6. **Pengujian & Ganti Password**:
   - Buka URL website di peramban.
   - Login dengan kredensial default (`admin` / `admin123` / PIN: `123456`).
   - Masuk ke menu **Pengaturan → Akun Pengguna** dan segera perbarui kata sandi serta PIN Anda.

---

## 3. Deployment pada VPS Linux (Ubuntu / Debian + Nginx / Apache)

### A. Konfigurasi Nginx (Virtual Host)

Jika menggunakan Nginx dengan PHP-FPM:

```nginx
server {
    listen 80;
    server_name realisasi.domainanda.com;
    root /var/www/realisasi/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Blokir akses langsung ke file tersembunyi dan database
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ \.(sqlite|sqlite3|db)$ {
        deny all;
    }
}
```

### B. Konfigurasi Kepemilikan & Hak Akses

```bash
cd /var/www/realisasi
sudo chown -R www-data:www-data .
sudo chmod -R 775 writable
```

---

## 4. Pencadangan Database (Backup & Restore)

Karena SIMRAN menggunakan SQLite, pencadangan database sangat mudah dan tidak membutuhkan dump MySQL:

### Backup Rutin:
Cukup salin berkas database:
```bash
cp writable/database/simran.sqlite /lokasi_backup/simran_$(date +%Y%m%d).sqlite
```

### Restore:
Ganti kembali file di `writable/database/simran.sqlite` dengan file backup yang diinginkan, kemudian pastikan permission file tetap dapat ditulis oleh web server.

