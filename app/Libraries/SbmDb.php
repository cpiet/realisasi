<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Koneksi ke database SBM (sbm.sqlite, terpisah dari simran.sqlite).
 * Skema dan data awal (dari app/Libraries/sbm_data/*.json) dibuat otomatis saat file masih kosong.
 */
class SbmDb
{
    private static ?BaseConnection $conn = null;

    public static function connect(): BaseConnection
    {
        if (self::$conn === null) {
            self::$conn = \Config\Database::connect('sbm');
            self::ensureSchema(self::$conn);
        }
        return self::$conn;
    }

    /** Lokasi file database SBM (untuk backup). */
    public static function path(): string
    {
        return WRITEPATH . 'database' . DIRECTORY_SEPARATOR . 'sbm.sqlite';
    }

    private static function ensureSchema(BaseConnection $db): void
    {
        if ($db->tableExists('sbm_regulasi')) {
            self::upgrade($db);
            return;
        }

        set_time_limit(120);
        $db->transStart();
        foreach (self::schema() as $sql) {
            $db->query($sql);
        }
        self::seed($db);
        $db->transComplete();
    }

    /**
     * Database lama (PMK 39/2024) -> PMK 32/2025. File dicadangkan dulu. Bila belum ada perubahan lewat admin
     * (sbm_log kosong) seluruh data di-seed ulang; bila sudah ada, data lama dibiarkan dan PMK 32/2025 ditambahkan.
     */
    private static function upgrade(BaseConnection $db): void
    {
        $ada = $db->table('sbm_regulasi')->where('nomor', '32 Tahun 2025')->countAllResults();
        if ($ada > 0) {
            return;
        }
        $path = self::path();
        if (is_file($path)) {
            @copy($path, $path . '.bak-' . date('Ymd-His'));
        }
        set_time_limit(120);
        $db->transStart();
        if ($db->table('sbm_log')->countAllResults() === 0) {
            foreach (['sbm_alias', 'sbm_harga', 'sbm_item', 'sbm_regulasi', 'sbm_wilayah'] as $t) {
                $db->table($t)->emptyTable();
            }
        }
        self::seed($db);
        $db->transComplete();
    }

    /** @return list<string> */
    private static function schema(): array
    {
        return [
            'CREATE TABLE sbm_regulasi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jenis TEXT NOT NULL,
                nomor TEXT NOT NULL,
                judul TEXT NOT NULL,
                tahun_anggaran INTEGER,
                status TEXT NOT NULL DEFAULT \'berlaku\',
                punya_nominal INTEGER NOT NULL DEFAULT 1,
                catatan TEXT,
                created_at TEXT,
                updated_at TEXT
            )',
            'CREATE TABLE sbm_wilayah (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama TEXT NOT NULL UNIQUE,
                alias TEXT,
                urutan INTEGER NOT NULL DEFAULT 0
            )',
            'CREATE TABLE sbm_item (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                regulasi_id INTEGER NOT NULL REFERENCES sbm_regulasi(id) ON DELETE CASCADE,
                lampiran TEXT NOT NULL,
                kode TEXT NOT NULL,
                kode_kanonik TEXT,
                nama TEXT NOT NULL,
                sifat TEXT NOT NULL DEFAULT \'batas_tertinggi\',
                deskripsi TEXT,
                penjelasan TEXT,
                halaman_tabel TEXT,
                halaman_penjelasan TEXT,
                UNIQUE (regulasi_id, lampiran, kode)
            )',
            'CREATE TABLE sbm_harga (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                item_id INTEGER NOT NULL REFERENCES sbm_item(id) ON DELETE CASCADE,
                kelompok TEXT NOT NULL DEFAULT \'\',
                kolom TEXT NOT NULL DEFAULT \'\',
                sub_uraian TEXT,
                wilayah_id INTEGER REFERENCES sbm_wilayah(id),
                satuan TEXT,
                besaran INTEGER,
                mata_uang TEXT NOT NULL DEFAULT \'IDR\',
                keterangan TEXT,
                halaman TEXT,
                needs_review INTEGER NOT NULL DEFAULT 0,
                urutan INTEGER NOT NULL DEFAULT 0
            )',
            'CREATE TABLE sbm_alias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode_kanonik TEXT NOT NULL,
                kata TEXT NOT NULL
            )',
            'CREATE TABLE sbm_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                harga_id INTEGER,
                item_id INTEGER,
                keterangan TEXT NOT NULL,
                nilai_lama TEXT,
                nilai_baru TEXT,
                oleh TEXT,
                created_at TEXT
            )',
            'CREATE INDEX idx_sbm_item_reg ON sbm_item(regulasi_id, kode_kanonik)',
            'CREATE INDEX idx_sbm_harga_item ON sbm_harga(item_id, wilayah_id)',
            'CREATE INDEX idx_sbm_alias_kata ON sbm_alias(kata)',
        ];
    }

    /** Aturan alias pencarian: pola pada nama item => kata kunci tambahan. */
    private const ALIAS_RULES = [
        '/narasumber|moderator/i' => ['narsum', 'narasumber', 'moderator', 'pembawa acara', 'panitia', 'honor narasumber'],
        '/penginapan/i' => ['hotel', 'penginapan', 'menginap', 'akomodasi'],
        '/uang harian/i' => ['uang harian', 'sppd', 'perjalanan dinas', 'dinas'],
        '/rapat\/pertemuan di luar kantor/i' => ['meeting', 'paket meeting', 'rapat luar kantor'],
        '/konsumsi/i' => ['snack', 'makan', 'kudapan', 'konsumsi', 'nasi kotak'],
        '/penanggung jawab pengelola keuangan/i' => ['ppk', 'kpa', 'bendahara', 'spm', 'honor ppk'],
        '/pengadaan barang\/jasa/i' => ['pokja', 'pejabat pengadaan', 'pbj'],
        '/tiket/i' => ['pesawat', 'tiket', 'airfare'],
        '/sewa kendaraan/i' => ['rental', 'sewa mobil', 'sewa kendaraan'],
        '/lembur/i' => ['lembur', 'overtime'],
        '/pakaian dinas/i' => ['seragam', 'pdh', 'pdl', 'baju dinas'],
        '/transport/i' => ['transport', 'taksi', 'ojek', 'angkutan'],
        '/uang makan/i' => ['uang makan', 'lauk pauk'],
        '/diklat|pendidikan dan pelatihan/i' => ['diklat', 'pelatihan', 'penceramah', 'pengajar'],
        '/tim pelaksana kegiatan/i' => ['tim', 'sk tim', 'honor tim'],
        '/satpam|pengemudi/i' => ['satpam', 'sopir', 'driver', 'cleaning service', 'pramubakti'],
        '/fotokopi/i' => ['fotokopi', 'fotocopy', 'mesin fotokopi'],
        '/gedung pertemuan/i' => ['gedung', 'aula', 'ballroom', 'sewa gedung'],
    ];

    private static function seed(BaseConnection $db): void
    {
        $now = date('Y-m-d H:i:s');
        $dir = __DIR__ . '/sbm_data/';

        $adaWilayah = $db->table('sbm_wilayah')->countAllResults() > 0;
        foreach (SbmSeedData::PROVINSI as $i => [$nama, $alias]) {
            if ($adaWilayah) {
                break;
            }
            $db->table('sbm_wilayah')->insert(['nama' => $nama, 'alias' => $alias, 'urutan' => $i + 1]);
        }
        $wil = array_column($db->table('sbm_wilayah')->orderBy('urutan')->get()->getResultArray(), 'id');

        // --- PMK 32/2025 (SBM TA 2026): lengkap dengan nominal ---
        $d39 = json_decode((string)file_get_contents($dir . 'pmk32_2025.json'), true);
        $db->table('sbm_regulasi')->insert([
            'jenis' => 'PMK', 'nomor' => '32 Tahun 2025',
            'judul' => 'Standar Biaya Masukan Tahun Anggaran 2026',
            'tahun_anggaran' => 2026, 'status' => 'berlaku', 'punya_nominal' => 1,
            'catatan' => 'Dipakai sebagai acuan sementara RKA TA 2027 selama nominal PMK 54/2026 belum ditetapkan (PMK 32/2025 menggantikan PMK 39/2024).',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $reg39 = (int)$db->insertID();

        foreach ($d39['items'] as $it) {
            $db->table('sbm_item')->insert([
                'regulasi_id' => $reg39, 'lampiran' => $it['lampiran'], 'kode' => $it['kode'], 'kode_kanonik' => $it['kanonik'],
                'nama' => $it['nama'], 'sifat' => $it['sifat'], 'penjelasan' => $it['penjelasan'],
                'halaman_tabel' => $it['hal_tabel'], 'halaman_penjelasan' => $it['hal_penjelasan'],
            ]);
            $itemId = (int)$db->insertID();
            $batch = [];
            foreach ($it['rows'] as $n => [$kel, $kol, $sub, $w, $sat, $bes, $cur, $hal, $rev, $ket]) {
                $batch[] = [
                    'item_id' => $itemId, 'kelompok' => $kel !== '' ? $kel : $it['nama'], 'kolom' => $kol, 'sub_uraian' => $sub,
                    'wilayah_id' => $w === null ? null : $wil[$w], 'satuan' => $sat, 'besaran' => $bes,
                    'mata_uang' => $cur, 'keterangan' => $ket, 'halaman' => $hal, 'needs_review' => $rev, 'urutan' => $n + 1,
                ];
                if (count($batch) >= 80) {
                    $db->table('sbm_harga')->insertBatch($batch);
                    $batch = [];
                }
            }
            if ($batch) {
                $db->table('sbm_harga')->insertBatch($batch);
            }
        }
        foreach (['I' => 'Batas Tertinggi', 'II' => 'Dapat Dilampaui'] as $lamp => $label) {
            if (!empty($d39['catatan_umum'][$lamp])) {
                $db->table('sbm_item')->insert([
                    'regulasi_id' => $reg39, 'lampiran' => $lamp, 'kode' => 'CU', 'kode_kanonik' => 'catatan_umum_' . strtolower($lamp),
                    'nama' => 'Catatan Umum Lampiran ' . $lamp . ' (' . $label . ')',
                    'sifat' => $lamp === 'I' ? 'batas_tertinggi' : 'dapat_dilampaui',
                    'penjelasan' => $d39['catatan_umum'][$lamp], 'halaman_penjelasan' => $lamp === 'I' ? '60-61' : '109-110',
                ]);
            }
        }

        // --- PMK 54/2026 (RKA TA 2027): nama + deskripsi, nominal belum ditetapkan ---
        $d54 = json_decode((string)file_get_contents($dir . 'pmk54_2026.json'), true);
        $ada54 = $db->table('sbm_regulasi')->where('nomor', '54 Tahun 2026')->countAllResults() > 0;
        if (!$ada54) {
        $db->table('sbm_regulasi')->insert([
            'jenis' => 'PMK', 'nomor' => '54 Tahun 2026', 'judul' => 'Standar Biaya Masukan',
            'tahun_anggaran' => 2027, 'status' => 'belum_berlaku', 'punya_nominal' => 0,
            'catatan' => 'Rincian besaran dan penjelasan ditetapkan Menteri Keuangan (Pasal 3 ayat 3). Menunggu KMK.',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $reg54 = (int)$db->insertID();
        foreach ($d54['items'] as $it) {
            $db->table('sbm_item')->insert([
                'regulasi_id' => $reg54, 'lampiran' => $it['lampiran'], 'kode' => $it['kode'], 'kode_kanonik' => $it['kanonik'],
                'nama' => $it['nama'], 'sifat' => $it['sifat'], 'deskripsi' => $it['deskripsi'], 'halaman_tabel' => $it['hal_tabel'],
            ]);
        }
        }

        // --- alias pencarian per kode_kanonik ---
        $seen = [];
        foreach (array_merge($d39['items'], $d54['items']) as $it) {
            foreach (self::ALIAS_RULES as $re => $kata) {
                if (!preg_match($re, $it['nama'])) {
                    continue;
                }
                foreach ($kata as $k) {
                    $key = $it['kanonik'] . '|' . $k;
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $db->table('sbm_alias')->insert(['kode_kanonik' => $it['kanonik'], 'kata' => $k]);
                    }
                }
            }
        }
    }
}
