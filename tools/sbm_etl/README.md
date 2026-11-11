# ETL SBM (di luar aplikasi, tidak ikut produksi)

Menghasilkan `app/Libraries/sbm_data/*.json` dari PDF PMK. Butuh PHP + `smalot/pdfparser` (sudah ada di vendor).

```
php extract.php "<SBM 2025.pdf>" sbm2025        # teks per halaman -> out/sbm2025/pNNN.txt
php extract.php "<2026pmkeuangan054.pdf>" pmk54
php build39.php                                  # tabel + penjelasan PMK 39/2024 -> out/pmk39_2024.json
php build54.php                                  # nama + deskripsi PMK 54/2026   -> out/pmk54_2026.json
php finalize.php                                 # tautkan kode_kanonik, tulis ke app/Libraries/sbm_data/
php check.php [I-31]                             # ringkasan kualitas
```
Setelah JSON berubah, hapus `writable/database/sbm.sqlite` agar di-seed ulang (data hasil edit admin ikut hilang — backup dulu).
Baris yang parsernya ragu dilaporkan (stderr/`Report`), tidak ditebak.
