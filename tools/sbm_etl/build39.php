<?php
/**
 * ETL PMK 39/2024 (SBM TA 2025) -> app/Libraries/sbm_data/pmk39_2024.json
 * Pakai: php build39.php   (teks hasil extract.php harus ada di out/sbm2025)
 */
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/tables.php';

$dir = __DIR__ . '/out/sbm2025';
$pages = [];
foreach (glob("$dir/p*.txt") as $f) {
    $no = (int)substr(basename($f), 1, 3);
    $pages[$no] = preg_split('/\R/u', (string)file_get_contents($f));
}

function stream(array $pages, int $from, int $to): array
{
    $out = [];
    for ($p = $from; $p <= $to; $p++) {
        $ls = array_map('norm', $pages[$p] ?? []);
        $ls = array_values(array_filter($ls, static fn ($x) => $x !== '' || true));
        for ($i = 0; $i < count($ls); $i++) {
            $t = $ls[$i];
            if (preg_match('/^\d{1,2}(?:\.\d+)*\.?$/', $t)) {          // nomor sendirian: gabung dengan baris berikut
                $j = $i + 1;
                while ($j < count($ls) && $ls[$j] === '') { $j++; }
                if ($j < count($ls)) { $t .= ' ' . $ls[$j]; $i = $j; }
            }
            $out[] = ['p' => $p, 't' => $t];
        }
    }
    return $out;
}

/** Spesifikasi matriks ringkas. */
function M(string $name, array $cols, array $o = []): array
{
    return $o + ['type' => 'matrix', 'name' => $name, 'cols' => $cols, 'amt' => 'rp', 'sat' => null, 'wil' => 'prov', 'expect' => 38];
}

const REG_NEGARA = '/^(AMERIKA|EROPA|AFRIKA|ASIA|TIMUR TENGAH)\b/';
$PROV2 = ['Satpam dan Pengemudi', 'Petugas Kebersihan dan Pramubakti'];

/** [lampiran, no] => [sections]. Section: [regex|null, spec]. Item tanpa spesifikasi = daftar bertingkat. */
$SPECS = [
    'I-27' => [[null, M('Honorarium Satpam, Pengemudi, Petugas Kebersihan, dan Pramubakti', $PROV2, ['sat' => 'OB'])]],
    'I-28' => [
        ['/^28\.1 /', M('Uang Harian Perjalanan Dinas Dalam Negeri', ['Luar Kota', 'Dalam Kota > 8 Jam', 'Diklat'], ['sat' => 'OH'])],
        ['/^28\.2 /', M('Uang Representasi Perjalanan Dinas Dalam Negeri', ['Luar Kota', 'Dalam Kota > 8 Jam'], ['sat' => 'OH', 'wil' => 'label', 'expect' => 0])],
    ],
    'I-29' => [[null, M('Uang Harian Perjalanan Dinas Luar Negeri', ['Golongan A', 'Golongan B', 'Golongan C', 'Golongan D'], ['sat' => 'OH', 'amt' => 'usd', 'wil' => 'label', 'region' => REG_NEGARA, 'expect' => 0])]],
    'I-30' => [[null, M('Penginapan Perjalanan Dinas Dalam Negeri', ['Pejabat Negara/Pejabat Eselon I', 'Pejabat Negara Lainnya/Pejabat Eselon II', 'Pejabat Eselon III/Golongan IV', 'Pejabat Eselon IV/Golongan III/II/I'], ['sat' => 'OH'])]],
    'I-31' => [
        ['/^a\. Menteri dan Setingkat/', M('Paket Rapat Luar Kantor — Menteri dan Setingkat Menteri', ['Halfday', 'Fullday', 'Fullboard'], ['sat' => 'OP'])],
        ['/^b\. Pejabat Eselon I dan II/', M('Paket Rapat Luar Kantor — Pejabat Eselon I dan II', ['Halfday', 'Fullday', 'Fullboard'], ['sat' => 'OP'])],
        ['/^c\. Pejabat Eselon III/i', M('Paket Rapat Luar Kantor — Pejabat Eselon III ke Bawah', ['Halfday', 'Fullday', 'Fullboard'], ['sat' => 'OP'])],
        ['/^31\.2 /', M('Uang Harian Rapat/Pertemuan di Luar Kantor', ['Besaran'], ['sat' => 'OH', 'wil' => 'label', 'expect' => 0])],
    ],
    'I-32' => [[null, M('Tiket Perjalanan Dinas Pindah Luar Negeri (One Way)', ['Jakarta-Perwakilan Published', 'Jakarta-Perwakilan Business', 'Jakarta-Perwakilan First', 'Perwakilan-Jakarta Published', 'Perwakilan-Jakarta Business', 'Perwakilan-Jakarta First'], ['sat' => 'Orang/Kali', 'amt' => 'usd', 'wil' => 'label', 'expect' => 0])]],
    'I-33' => [[null, M('Operasional Khusus Kepala Perwakilan RI di Luar Negeri', ['Besaran'], ['sat' => 'OT', 'amt' => 'usd', 'wil' => 'label', 'region' => '/^(AMERIKA|EROPA|AFRIKA|ASIA|TIMUR TENGAH)\b/', 'expect' => 0])]],
    'I-34' => [[null, M('Makanan Penambah Daya Tahan Tubuh', ['Besaran'], ['sat' => 'OH'])]],
    'I-35' => [
        ['/^35\.1 /', M('Sewa Kendaraan Pelaksanaan Kegiatan Insidentil', ['Roda 4', 'Roda 6/Bus Sedang', 'Roda 6/Bus Besar'], ['sat' => 'Per hari'])],
        ['/^35\.2 /', M('Sewa Kendaraan Operasional Pejabat', ['Besaran'], ['sat' => 'Per bulan', 'wil' => 'prov_or_label', 'expect' => 38])],
        ['/^35\.3 /', M('Sewa Kendaraan Operasional Kantor dan/atau Lapangan', ['Pick Up', 'Minibus', 'Double Gardan'], ['sat' => 'Per bulan'])],
    ],
    'I-36' => [
        ['/^36\.1 /', M('Pengadaan Kendaraan Dinas Pejabat', ['Besaran'], ['sat' => 'Unit', 'wil' => 'prov_or_label', 'expect' => 38])],
        ['/^36\.2 /', M('Pengadaan Kendaraan Pejabat Eselon III/Operasional Kantor dan/atau Lapangan Roda 4', ['Pick Up', 'Pejabat Eselon III/Minibus', 'Double Gardan'], ['sat' => 'Unit'])],
        ['/^36\.3 /', M('Pengadaan Kendaraan Operasional Bus', ['Besaran'], ['sat' => 'Unit', 'wil' => 'label', 'expect' => 0])],
        ['/^36\.4 /', M('Pengadaan Kendaraan Operasional Kantor dan/atau Lapangan Roda 2', ['Operasional', 'Lapangan'], ['sat' => 'Unit'])],
        ['/^36\.5 /', M('Pengadaan Kendaraan Listrik Berbasis Baterai', ['Besaran'], ['sat' => 'Unit', 'wil' => 'label', 'expect' => 0])],
    ],
    'I-37' => [[null, M('Pengadaan Pakaian Dinas', ['Pakaian Dinas Dokter', 'Pakaian Dinas Pegawai/Perawat', 'Pakaian Seragam Mahasiswa/Taruna', 'Pakaian Kerja Pengemudi/Petugas Kebersihan/Pramubakti', 'Pakaian Kerja Satpam'], ['sat' => 'Setel'])]],
    'I-38' => [[null, M('Konsumsi Rapat/Pertemuan', ['Makan', 'Kudapan (Snack)'], ['sat' => 'Orang/Kali', 'wil' => 'prov_or_label', 'expect' => 38])]],
    'I-39' => [[null, M('Konsumsi Kegiatan Pendidikan dan Pelatihan (Diklat)', ['Makan', 'Kudapan (Snack)'], ['sat' => 'Orang/Kali'])]],

    'II-1' => [[null, M('Transportasi Darat dari Ibu Kota Provinsi ke Kabupaten/Kota dalam Provinsi yang Sama (One Way)', ['Besaran'], ['sat' => 'Orang/Kali', 'wil' => 'region', 'region' => 'province', 'transport' => true, 'expect' => 0])]],
    'II-2' => [[null, M('Transportasi dari DKI Jakarta ke Kabupaten/Kota Sekitar (One Way)', ['Besaran'], ['sat' => 'Orang/Kali', 'wil' => 'fixed:12', 'expect' => 0])]],
    'II-9' => [
        ['/^9\.1 /', M('Bahan Makanan Narapidana/Tahanan dan Anak di Lapas/Rutan Kemenkumham', ['Besaran'], ['sat' => 'OH'])],
        ['/^9\.2 /', M('Bahan Makanan Operasi Pasukan, Dikma, Diklat, dsb. Anggota Polri/TNI', ['Operasi Pasukan/Latihan Pratugas/Latihan Pasukan Lainnya', 'Dikma/Taruna/Karbol/Kadet', 'Diklat Lainnya Kemhan/Polri/TNI', 'Anggota yang Sakit', 'Tahanan Anggota Polri/TNI', 'Jaga Kawal'], ['sat' => 'OH'])],
        ['/^9\.3 /', M('Bahan Makanan Pasien Rumah Sakit dan PMKS', ['Pasien Rumah Sakit', 'PMKS'], ['sat' => 'OH'])],
        ['/^9\.4 /', M('Bahan Makanan Keluarga PMS, Petugas Pengamatan Laut, ABK, SROP dan VTIS', ['Keluarga PMS', 'Petugas Pengamatan Laut', 'ABK Cadangan pada Kapal Negara', 'ABK Aktif pada Kapal Negara', 'Petugas SROP dan VTIS'], ['sat' => 'OH'])],
        ['/^9\.5 /', M('Bahan Makanan Petugas Bengkel/Galangan Kapal, Pabrik Gas Aga, PMS, Tenaga Kesehatan Kerja Pelayaran', ['Petugas Bengkel dan Galangan Kapal Kenavigasian', 'Petugas Pabrik Gas Aga untuk Lampu Suar', 'PMS', 'Kelompok Tenaga Kesehatan Kerja Pelayaran'], ['sat' => 'OH'])],
        ['/^9\.6 /', M('Bahan Makanan Mahasiswa/Siswa di Sekolah Kedinasan', ['Mahasiswa/Siswa Sipil', 'Mahasiswa Militer/Semi Militer'], ['sat' => 'OH'])],
        ['/^9\.7 /', M('Bahan Makanan Rescue Team', ['Besaran'], ['sat' => 'OH'])],
    ],
    'II-10' => [[null, M('Konsumsi Tahanan/Deteni/ABK Nonjustisia', ['Besaran'], ['sat' => 'OH'])]],
    'II-11' => [[null, M('Keperluan Sehari-hari Perkantoran di Dalam Negeri', ['Satker s.d. 40 Pegawai', 'Satker > 40 Pegawai'], [])]],
    'II-12' => [[null, M('Penggantian Inventaris Lama dan/atau Pembelian Inventaris Pegawai Baru', ['Besaran'], ['sat' => 'Pegawai/Tahun'])]],
    'II-13' => [
        ['/^13\.1 /', M('Pemeliharaan dan Operasional Kendaraan Dinas Pejabat', ['Besaran'], ['sat' => 'Unit/Tahun', 'wil' => 'prov_or_label', 'expect' => 34])],
        ['/^13\.2 /', M('Pemeliharaan dan Operasional Kendaraan Dinas Operasional', ['Roda Empat', 'Double Gardan', 'Roda Dua'], ['sat' => 'Unit/Tahun', 'expect' => 34])],
        ['/^13\.3 /', M('Operasional Lingkungan Kantor, Roda 6, Tahanan Kejaksaan, Speed Boat', ['Besaran'], ['sat' => 'Unit/Tahun', 'wil' => 'label', 'expect' => 0])],
        ['/^13\.4 /', M('Kendaraan Dinas Operasional Patroli Jalan Raya (PJR)', ['PJR Roda Empat', 'PJR Roda Dua (≤ 250 cc)', 'PJR Roda Dua (≥ 750 cc)'], ['sat' => 'Unit/Tahun', 'expect' => 34])],
        ['/^13\.5 /', M('Operasional Kendaraan Dinas Pengadaan dari Sewa', ['Besaran'], ['sat' => 'Unit/Tahun', 'wil' => 'label', 'expect' => 0])],
        ['/^13\.6 /', M('Kendaraan Bermotor Listrik Berbasis Baterai', ['Besaran'], ['sat' => 'Unit/Tahun', 'wil' => 'label', 'expect' => 0])],
    ],
    'II-14' => [[null, M('Pemeliharaan Gedung/Bangunan Dalam Negeri', ['Gedung Bertingkat', 'Gedung Tidak Bertingkat', 'Halaman Gedung/Bangunan Kantor'], ['sat' => 'm2/tahun'])]],
    'II-15' => [[null, M('Sewa Gedung Pertemuan', ['Besaran'], ['sat' => 'Per hari'])]],
    'II-16' => [[null, M('Transportasi dari dan/atau ke Terminal Bus/Stasiun/Bandara/Pelabuhan', ['Besaran'], ['sat' => 'Orang/Kali', 'expect' => 34])]],
    'II-17' => [[null, M('Tiket Pesawat Perjalanan Dinas Dalam Negeri Pergi Pulang (PP)', ['Bisnis', 'Ekonomi'], ['wil' => 'label', 'expect' => 0, 'pair' => [
        'JAKARTA', 'AMBON', 'BALIKPAPAN', 'BANDA ACEH', 'BANDAR LAMPUNG', 'BANDUNG', 'BANJARMASIN', 'BATAM', 'BENGKULU', 'BIAK', 'DENPASAR',
        'JAMBI', 'JAYAPURA', 'YOGYAKARTA', 'KENDARI', 'KUPANG', 'MAKASSAR', 'MALANG', 'MANADO', 'MATARAM', 'MEDAN', 'PADANG', 'PALANGKARAYA',
        'PALEMBANG', 'PALU', 'PANGKAL PINANG', 'PEKANBARU', 'PONTIANAK', 'SEMARANG', 'SOLO', 'SURABAYA']])]],
    'II-18' => [[null, M('Tiket Pesawat Perjalanan Dinas Luar Negeri Pergi Pulang (PP)', ['Eksekutif', 'Bisnis', 'Ekonomi'], ['amt' => 'usd', 'wil' => 'label', 'region' => REG_NEGARA, 'expect' => 0])]],
    'II-19' => [
        ['/^19\.1 /', M('Penyelenggaraan Perwakilan RI di Luar Negeri (19.1)', ['ATK (OT)', 'Langganan Koran/Majalah (Eksemplar/Bulan)', 'Lampu (Buah)', 'Pengamanan Sendiri (OB)', 'Kantong Diplomatik (kg)', 'Jamuan (OH)'], ['amt' => 'usd', 'wil' => 'label', 'region' => REG_NEGARA, 'expect' => 0])],
        ['/^19\.2 /', M('Penyelenggaraan Perwakilan RI di Luar Negeri (19.2)', ['Pemeliharaan Kendaraan Dinas (Unit/Tahun)', 'Pemeliharaan Gedung (m2/Tahun)', 'Pemeliharaan Halaman (m2/Tahun)', 'Pengadaan Inventaris Kantor (OT)', 'Pakaian Sopir/Satpam (Stel)', 'Sewa Sedan (hari)', 'Sewa Bus (hari)', 'Sewa Mobil Box (hari)', 'Konsumsi Rapat (OK)'], ['amt' => 'usd', 'wil' => 'label', 'region' => REG_NEGARA, 'expect' => 0])],
    ],
];

// ---------- temukan batas item (berurutan) ----------
function findItems(array $stream, int $expectMax): array
{
    $headRe = '/^(\d{1,2})\.?\s+((?:SATUAN BIAYA|HONOR\w+|BIAYA PAKET)[A-Z0-9 \/,().\-&\']*?)(?:\s+((?:' . satuanRegex() . ')\s+(?:' . AMT_RP . ')))?$/u';
    $items = [];
    $next = 1;
    foreach ($stream as $i => $ln) {
        if ($next > $expectMax) {
            break;
        }
        if (preg_match($headRe, $ln['t'], $m) && (int)$m[1] === $next) {
            $items[$next] = ['start' => $i, 'title' => $m[2], 'inline' => $m[3] ?? null, 'p' => $ln['p']];
            $next++;
        }
    }
    foreach ($items as $no => &$it) {
        $it['end'] = isset($items[$no + 1]) ? $items[$no + 1]['start'] : count($stream);
    }
    return $items;
}

function regionLines(array $stream, array $it): array
{
    $lines = array_slice($stream, $it['start'] + 1, $it['end'] - $it['start'] - 1);
    $title = $it['title'];
    // lanjutan judul: baris KAPITAL langsung setelah header
    while ($lines && preg_match('/^[A-Z0-9 \/,().\-&\']+$/', $lines[0]['t']) && !isNoise($lines[0]['t'])
        && !preg_match('/^\d/', $lines[0]['t'])) {
        $title .= ' ' . $lines[0]['t'];
        array_shift($lines);
    }
    if ($it['inline']) {
        array_unshift($lines, ['p' => $it['p'], 't' => $it['inline']]);
    }
    return [$title, $lines];
}

$out = ['lampiran' => []];
$allWarn = [];

foreach ([['I', 4, 40, 39], ['II', 65, 103, 19]] as [$lamp, $p1, $p2, $max]) {
    $stream = stream($pages, $p1, $p2);
    $found = findItems($stream, $max);
    if (count($found) !== $max) {
        Report::w("Lampiran $lamp: item terdeteksi " . count($found) . " dari $max");
    }
    $items = [];
    foreach ($found as $no => $it) {
        [$title, $lines] = regionLines($stream, $it);
        $key = "$lamp-$no";
        $tag = "[$lamp-$no]";
        $rows = [];
        if (isset($SPECS[$key])) {
            $cur = $SPECS[$key][0][0] === null ? $SPECS[$key][0][1] : null;
            $chunk = [];
            $sections = $SPECS[$key];
            $flushChunk = function () use (&$rows, &$chunk, &$cur, $tag) {
                if ($chunk && $cur) {
                    $rows = array_merge($rows, parseMatrix($chunk, $cur, $tag . ' ' . substr($cur['name'], 0, 25)));
                }
                $chunk = [];
            };
            foreach ($lines as $ln) {
                $hit = null;
                foreach ($sections as [$re, $sp]) {
                    if ($re !== null && preg_match($re, $ln['t'] . ' ')) { $hit = $sp; break; }
                }
                if ($hit) {
                    $flushChunk();
                    $cur = $hit;
                    continue;
                }
                $chunk[] = $ln;
            }
            $flushChunk();
        } else {
            $rows = parseList($lines, $tag, ucwords(mb_strtolower($title)));
        }
        $pgs = array_column($rows, 'p');
        $items[$no] = [
            'no' => (string)$no, 'title_table' => $title, 'rows' => $rows,
            'hal_tabel' => $pgs ? (min($pgs) === max($pgs) ? (string)min($pgs) : min($pgs) . '-' . max($pgs)) : null,
        ];
        printf("%s %-3s %5d baris  %s\n", $lamp, $no, count($rows), mb_substr($title, 0, 70));
    }
    $out['lampiran'][$lamp] = $items;
}

// ---------- penjelasan ----------
require __DIR__ . '/text.php';
$VOCAB = buildVocab($pages);

foreach ([['I', 41, 62, 39], ['II', 104, 112, 19]] as [$lamp, $p1, $p2, $max]) {
    $lines = cleanLines(stream($pages, $p1, $p2));
    $heads = [];
    $next = 1;
    foreach ($lines as $i => $l) {
        if ($next > $max) { break; }
        if (preg_match('/^' . $next . '\.\s+([A-Z][^\n]*[^.:;])$/u', $l['t'], $m)) {
            $heads[$next] = ['i' => $i, 'name' => $m[1], 'p' => $l['p']];
            $next++;
        }
    }
    if (count($heads) !== $max) {
        Report::w("Penjelasan $lamp: judul terdeteksi " . count($heads) . " dari $max");
    }
    foreach ($heads as $no => $h) {
        $end = isset($heads[$no + 1]) ? $heads[$no + 1]['i'] : count($lines);
        $body = array_slice($lines, $h['i'] + 1, $end - $h['i'] - 1);
        // lanjutan judul: pilih penambahan baris yang menaikkan kemiripan dengan judul tabel
        $tt = nkey($out['lampiran'][$lamp][$no]['title_table'] ?? '');
        $name = $h['name'];
        for ($k = 0; $k < 2 && $body; $k++) {
            $cand = $name . ' ' . $body[0]['t'];
            similar_text(nkey($name), $tt, $s0);
            similar_text(nkey($cand), $tt, $s1);
            if ($s1 > $s0 + 0.5 && strlen($body[0]['t']) < 90) { $name = $cand; array_shift($body); } else { break; }
        }
        foreach ($body as $bi => $bl) {
            if (str_starts_with($bl['t'], 'Catatan Umum')) { $body = array_slice($body, 0, $bi); break; }
        }
        $pgs = array_column($body, 'p');
        $out['lampiran'][$lamp][$no]['nama'] = fixSplits($name, $VOCAB);
        $out['lampiran'][$lamp][$no]['penjelasan'] = fixSplits(implode("\n", array_map(static function ($p) {
            return preg_match('/^(?:[a-z]\.\s|\d+\)\s|[a-z]\)\s|\d+\.\s)/u', $p) ? '• ' . $p : $p;
        }, paragraphs($body))), $VOCAB);
        $out['lampiran'][$lamp][$no]['hal_penjelasan'] = $pgs ? (min($pgs) === max($pgs) ? (string)min($pgs) : min($pgs) . '-' . max($pgs)) : null;
    }
}

// Catatan umum
$strip = static fn (array $ls) => array_values(array_filter($ls, static fn ($l) => !preg_match('/^(MENTERI KEUANGAN|ttd\.|SRI MULYANI|_+$)/', $l['t'])));
$cu1 = $strip(cleanLines(stream($pages, 63, 64)));
$cu2 = $strip(cleanLines(stream($pages, 112, 113)));
$out['catatan_umum'] = [
    'I' => fixSplits(implode("\n", paragraphs(array_slice($cu1, array_search(true, array_map(static fn ($l) => str_starts_with($l['t'], 'Catatan Umum'), $cu1), true) ?: 0))), $VOCAB),
    'II' => fixSplits(implode("\n", paragraphs(array_slice($cu2, array_search(true, array_map(static fn ($l) => str_starts_with($l['t'], 'Catatan Umum'), $cu2), true) ?: 0))), $VOCAB),
];

@mkdir(__DIR__ . '/../../app/Libraries/sbm_data', 0777, true);
file_put_contents(__DIR__ . '/out/pmk39_2024.json', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
echo "\n== PERINGATAN (" . count(Report::$warn) . ") ==\n";
foreach (array_slice(Report::$warn, 0, 80) as $w) { echo " - $w\n"; }
