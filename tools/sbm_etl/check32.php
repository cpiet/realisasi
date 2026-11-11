<?php
// Ringkasan kualitas hasil ETL PMK 39/2024. Pakai: php check.php [I-31]  (tampilkan baris satu item)
$d = json_decode(file_get_contents(__DIR__ . '/out/pmk32_2025.json'), true);
foreach (['I', 'II'] as $lp) {
    foreach ($d['lampiran'][$lp] as $no => $it) {
        $rows = $it['rows'];
        $rev = count(array_filter($rows, fn ($r) => $r['review']));
        $nosat = count(array_filter($rows, fn ($r) => !$r['satuan'] && $r['besaran'] !== null));
        $txt = count(array_filter($rows, fn ($r) => $r['besaran'] === null));
        echo sprintf("[%s-%-2s] baris=%-5d perlu_verifikasi=%d tanpa_satuan=%d nilai_teks=%d  %s\n", $lp, $no, count($rows), $rev, $nosat, $txt, $it['nama'] ?? '?');
    }
}
if (!empty($argv[1])) {
    [$lp, $no] = explode('-', $argv[1]);
    foreach ($d['lampiran'][$lp][$no]['rows'] as $r) {
        echo json_encode([$r['kelompok'], $r['kolom'], $r['satuan'], $r['besaran'], $r['cur'], $r['wil'], $r['p']], JSON_UNESCAPED_UNICODE), "\n";
    }
}
