<?php
/**
 * ETL PMK 54/2026 (SBM untuk RKA mulai TA 2027): hanya nama + deskripsi, tanpa nominal.
 * Hasil: out/pmk54_2026.json
 */
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/text.php';

$pages = [];
foreach (glob(__DIR__ . '/out/pmk54/p*.txt') as $f) {
    $pages[(int)substr(basename($f), 1, 3)] = preg_split('/\R/u', (string)file_get_contents($f));
}
$all = $pages;
foreach (glob(__DIR__ . '/out/sbm2025/p*.txt') as $f2) { $all[] = preg_split('/\R/u', (string)file_get_contents($f2)); }
$VOCAB = buildVocab($all);

$descStart = '/^(Honorarium\s+(yang|diberikan|dapat)|Satuan\s+[Bb]iaya|Uang\s+harian|Kelompok\s+Honorarium|Biaya\s+yang)/u';
$out = [];
foreach ([['I', 5, 9, 39], ['II', 10, 12, 19]] as [$lamp, $p1, $p2, $max]) {
    $lines = [];
    for ($p = $p1; $p <= $p2; $p++) {
        foreach ($pages[$p] ?? [] as $raw) {
            $t = norm($raw);
            if ($t === '' || preg_match('/^\d{1,2}$/', $t) || preg_match('/^(LAMPIRAN|PERATURAN MENTERI|NOMOR 54|TENTANG STANDAR|STANDAR BIAYA MASUKAN|YANG BERSIFAT|Dokumen ini|elektronik, silakan|_+$)/', $t)) { continue; }
            if (preg_match('/^(MENTERI KEUANGAN REPUBLIK INDONESIA,|ttd\.|PURBAYA YUDHI SADEWA)$/', $t)) { continue; }
            if (preg_match('/^[A-E]\. SATUAN BIAYA/', $t)) { continue; }
            $lines[] = ['p' => $p, 't' => $t];
        }
    }
    $heads = [];
    $next = 1;
    foreach ($lines as $i => $l) {
        if ($next > $max) { break; }
        if (preg_match('/^\s*' . $next . '\.\s*(\p{Lu}.*)$/u', $l['t'], $m)) {
            $heads[$next] = ['i' => $i, 'name' => $m[1], 'p' => $l['p']];
            $next++;
        }
    }
    if (count($heads) !== $max) { fwrite(STDERR, "Lampiran $lamp: judul terdeteksi " . count($heads) . " dari $max\n"); }
    foreach ($heads as $no => $h) {
        $end = isset($heads[$no + 1]) ? $heads[$no + 1]['i'] : count($lines);
        $body = array_slice($lines, $h['i'] + 1, $end - $h['i'] - 1);
        $name = $h['name'];
        while ($body && !preg_match($descStart, $body[0]['t']) && mb_strlen($name) < 200) {
            $name .= ' ' . $body[0]['t'];
            array_shift($body);
            if (count($body) < 1) { break; }
        }
        $paras = paragraphs($body);
        $desc = fixSplits(implode("\n", array_map(static fn ($p) => preg_match('/^(?:[a-z]\.\s|\d+\)\s)/u', $p) ? '• ' . $p : $p, $paras)), $VOCAB);
        $pg = array_column($body, 'p');
        $out[$lamp][$no] = [
            'no' => (string)$no,
            'nama' => preg_replace('/\s+/u', ' ', fixSplits(trim(preg_replace('/\s*\/\s*/u', '/', $name)), $VOCAB)),
            'deskripsi' => $desc,
            'hal' => $h['p'] === ($pg ? max($pg) : $h['p']) ? (string)$h['p'] : $h['p'] . '-' . max($pg),
        ];
        printf("%s %2d  %s\n", $lamp, $no, $out[$lamp][$no]['nama']);
    }
}
file_put_contents(__DIR__ . '/out/pmk54_2026.json', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
