<?php
/** Gabungkan hasil ETL -> app/Libraries/sbm_data/*.json dan tautkan kode_kanonik antar-regulasi. */
$d39 = json_decode(file_get_contents(__DIR__ . '/out/pmk32_2025.json'), true);
$d54 = json_decode(file_get_contents(__DIR__ . '/out/pmk54_2026.json'), true);

function base(string $n): string
{
    $n = mb_strtolower($n, 'UTF-8');
    $n = preg_replace('/^satuan biaya\s+/', '', $n);
    return preg_replace('/[^a-z0-9]+/', '', $n);
}
function slug(string $n): string
{
    $n = mb_strtolower(preg_replace('/^satuan biaya\s+/i', '', $n), 'UTF-8');
    $n = preg_replace('/\([^)]*\)/', '', $n);
    $n = trim(preg_replace('/[^a-z0-9]+/', '_', $n), '_');
    return substr($n, 0, 60);
}

$items39 = [];
foreach (['I', 'II'] as $lp) {
    foreach ($d39['lampiran'][$lp] as $no => $it) {
        $rows = [];
        foreach ($it['rows'] as $r) {
            $rows[] = [$r['kelompok'], $r['kolom'], $r['sub'], $r['wil'], $r['satuan'], $r['besaran'], $r['cur'], (string)$r['p'], (int)$r['review'], $r['ket']];
        }
        $items39[] = [
            'lampiran' => $lp, 'kode' => (string)$no, 'kanonik' => slug($it['nama']), 'nama' => $it['nama'],
            'sifat' => $lp === 'I' ? 'batas_tertinggi' : 'dapat_dilampaui',
            'penjelasan' => $it['penjelasan'] ?? null, 'hal_tabel' => $it['hal_tabel'], 'hal_penjelasan' => $it['hal_penjelasan'] ?? null,
            'rows' => $rows,
        ];
    }
}
$byBase = [];
foreach ($items39 as $i => $it) { $byBase[base($it['nama'])][] = $i; }

$items54 = [];
$unmatched = [];
// padanan manual: judul 2026 berbeda dari 2025 tetapi satuan biaya yang sama
$manual = [
    'Honorarium Penyuluh Non Aparatur Sipil Negara (ASN)' => 'Honorarium Penyuluh Nonpegawai Negeri Sipil',
    'Honorarium Penyusunan Jurnal dan Pembuat Artikel' => 'Honorarium Penyusunan Jurnal',
    'Satuan Biaya Uang Makan bagi Pegawai ASN dan Uang Lauk Pauk bagi Prajurit TNI/Anggota POLRI' => 'Satuan Biaya Uang Makan Bagi Pegawai Aparatur Sipil Negara dan Uang Lauk Pauk bagi Anggota Polri/TNI',
    'Satuan Biaya Uang Lembur dan Uang Makan Lembur bagi Pegawai ASN' => 'Satuan Biaya Uang Lembur dan Uang Makan Lembur Bagi Pegawai Aparatur Sipil Negara',
    'Satuan Biaya Kebutuhan Dasar Perkantoran di Dalam Negeri' => 'Satuan Biaya Keperluan Sehari-hari Perkantoran di Dalam Negeri',
];
$kanByName = [];
foreach ($items39 as $c) { $kanByName[$c['nama']] = $c['kanonik']; }
foreach (['I', 'II'] as $lp) {
    foreach ($d54[$lp] as $no => $it) {
        $b = base($it['nama']);
        $kan = null;
        if (isset($manual[$it['nama']]) && isset($kanByName[$manual[$it['nama']]])) { $kan = $kanByName[$manual[$it['nama']]]; }
        elseif (isset($byBase[$b])) { $kan = $items39[$byBase[$b][0]]['kanonik']; }
        else {
            $best = 0; 
            foreach ($items39 as $c) { similar_text($b, base($c['nama']), $pc); if ($pc > $best) { $best = $pc; $kan = $c['kanonik']; $bn = $c['nama']; } }
            if ($best < 85) { $unmatched[] = "{$lp}-{$no} {$it['nama']} (terdekat {$best}%: " . ($bn ?? '-') . ')'; $kan = slug($it['nama']); }
            else { echo "mirip {$lp}-{$no}: {$it['nama']}  ≈  $bn (" . round($best) . "%)\n"; }
        }
        $items54[] = [
            'lampiran' => $lp, 'kode' => (string)$no, 'kanonik' => $kan, 'nama' => $it['nama'],
            'sifat' => $lp === 'I' ? 'batas_tertinggi' : 'dapat_dilampaui',
            'deskripsi' => $it['deskripsi'], 'hal_tabel' => $it['hal'],
        ];
    }
}
echo "\nTidak ada padanan di PMK 32 (" . count($unmatched) . "):\n  " . implode("\n  ", $unmatched) . "\n";
$dir = __DIR__ . '/../../app/Libraries/sbm_data';
@mkdir($dir, 0777, true);
file_put_contents("$dir/pmk32_2025.json", json_encode(['items' => $items39, 'catatan_umum' => $d39['catatan_umum']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents("$dir/pmk54_2026.json", json_encode(['items' => $items54], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "\nitem39=" . count($items39) . " item54=" . count($items54) . " ukuran=" . round(filesize("$dir/pmk32_2025.json") / 1024) . " KB\n";
