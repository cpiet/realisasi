<?php
/**
 * Parser tabel Lampiran PMK 39/2024: matriks (provinsi/negara/kota) dan daftar bertingkat.
 * Dilarang menebak angka: baris yang tidak utuh dilaporkan, bukan diperbaiki diam-diam.
 */

require_once __DIR__ . '/lib.php';

class Report
{
    public static array $warn = [];
    public static function w(string $m): void { self::$warn[] = $m; }
}

/** HURUF KAPITAL SEMUA -> Huruf Judul, angka romawi tetap kapital. */
function pretty(string $s): string
{
    if ($s !== mb_strtoupper($s, 'UTF-8') || mb_strlen($s) < 4) {
        return $s;
    }
    $t = ucwords(mb_strtolower($s, 'UTF-8'), " /(-");
    return preg_replace_callback('/\b(Ii|Iii|Iv|Vi)\b/', static fn ($m) => strtoupper($m[1]), $t);
}

function cleanSat(?string $s): ?string
{
    if ($s === null) {
        return null;
    }
    $s = preg_replace('/km\/m ?3/', 'km/m3', $s);
    $s = preg_replace('/^m ?3$/', 'm3', $s);
    $s = preg_replace('/^m ?2\/tahun$/i', 'm2/tahun', $s);
    $s = preg_replace('/Penelitian\/ ?Perekayasaan/', 'Penelitian/Perekayasaan', $s);
    return trim($s);
}

/** Pecah ekor baris menjadi label + N kolom nilai (dari kanan). */
function splitTail(string $t, int $n, string $amtMode): ?array
{
    $amt = $amtMode === 'usd' ? AMT_USD : AMT_RP . '|' . AMT_TXT;
    $sat = satuanRegex();
    $cols = [];
    $rest = $t;
    for ($i = 0; $i < $n; $i++) {
        if (!preg_match('/^(.*?)(?:\s+|^)(?:(' . $sat . ')\s+)?(' . $amt . ')\s*$/u', $rest, $m, PREG_UNMATCHED_AS_NULL)) {
            return null;
        }
        $cols[] = ['sat' => $m[2], 'amt' => trim($m[3])];
        $rest = rtrim($m[1]);
    }
    return ['label' => $rest, 'cols' => array_reverse($cols)];
}

/**
 * @param list<array{p:int,t:string}> $lines
 * @return list<array<string,mixed>>
 */
function parseMatrix(array $lines, array $spec, string $itemTag): array
{
    $n = count($spec['cols']);
    $mode = $spec['amt'];
    $rows = [];
    $buf = null;
    $bufPage = 0;
    $region = null;
    $pending = '';
    $sub = null;
    $provMap = provinsiKeyMap();

    $flush = static function () use (&$buf, &$sub, $itemTag) {
        if ($buf !== null) {
            if (preg_match('/^\d+(?:\.\d+)*\.?\s+(.+)$/u', $buf, $mm) && !preg_match('/Rp\s?\d/', $buf)) {
                $sub = pretty(trim($mm[1]));
            } else {
                Report::w("$itemTag baris tidak utuh dibuang: $buf");
            }
            $buf = null;
        }
    };

    foreach ($lines as $ln) {
        $t = $ln['t'];
        if (isNoise($t)) {
            continue;
        }
        if (preg_match('/^\d+(?:\.\d+)*\.?\s+[A-Za-z(]/u', $t) && preg_match('/^\d{1,3}(?:\.|\s|$)/', $t)) {
            $flush();
            $buf = $t;
            $bufPage = $ln['p'];
            $pending = '';
        } elseif ($buf !== null) {
            $buf .= ' ' . $t;
        } else {
            $isCaps = (preg_match('/^[A-Z][A-Z .\/()&,\'-]*$/', $t)
                && !preg_match('/\b(SATUAN|BESARAN|URAIAN|PROVINSI|KOTA|PERWAKILAN|NEGARA|IBUKOTA)\b/', $t))
                || preg_match('/^[A-Z](\s[A-Z])+$/', $t);
            if ($isCaps) {
                $cand = trim($pending . ' ' . $t);
                if (($spec['region'] ?? '') === 'province') {
                    if (isset($provMap[nkey($cand)])) {
                        $region = $cand;
                        $pending = '';
                    } else {
                        $pending = $cand;
                    }
                } elseif (!empty($spec['region']) && preg_match($spec['region'], $cand)) {
                    $region = $cand;
                    $pending = '';
                }
            }
            continue;
        }

        $parts = splitTail(preg_replace('/^\d+(?:\.\d+)*\.?\s+/u', '', $buf), $n, $mode);
        if ($parts === null || $parts['label'] === '') {
            continue;
        }
        if (preg_match('/(?:^|\s)(?:' . ($mode === 'usd' ? '\d{1,3}(?:,\d{3})+' : AMT_RP) . ')$/u', $parts['label'])) {
            Report::w("$itemTag kolom lebih banyak dari spesifikasi: $buf");
        }
        $label = pretty($parts['label']);
        $rowNo = preg_match('/^(\d+(?:\.\d+)*)/', $buf, $mn) ? $mn[1] : '';
        $buf = null;

        $wilId = null;
        $isProv = false;
        if (str_starts_with($spec['wil'], 'fixed:')) {
            $wilId = (int)substr($spec['wil'], 6);
        } elseif ($spec['wil'] === 'region') {
            $wilId = $region ? ($provMap[nkey($region)] ?? null) : null;
            if ($wilId === null) {
                Report::w("$itemTag wilayah tidak dikenal untuk: $label (region=" . ($region ?? '-') . ')');
            }
        } elseif (in_array($spec['wil'], ['prov', 'prov_or_label'], true)) {
            $k = nkey($label);
            if (isset($provMap[$k])) {
                $wilId = $provMap[$k];
                $isProv = true;
            } elseif ($spec['wil'] === 'prov') {
                Report::w("$itemTag provinsi tidak dikenal: $label");
            }
        }
        if (!empty($spec['transport']) && preg_match('/^(.+?)\s+((?:Kab|Kota)\.\s.+)$/u', $label, $mt)) {
            $label = $mt[1] . ' → ' . $mt[2];
        }
        if (!empty($spec['pair'])) {
            $asal = null;
            foreach ($spec['pair'] as $city) {
                if (str_starts_with(mb_strtoupper($label) . ' ', $city . ' ') && ($asal === null || strlen($city) > strlen($asal))) {
                    $asal = $city;
                }
            }
            if ($asal === null) {
                Report::w("$itemTag asal tidak dikenal: $label");
            } else {
                $label = ucwords(mb_strtolower($asal)) . ' → ' . ucwords(mb_strtolower(trim(substr($label, strlen($asal)))));
            }
        }

        $carry = null;
        foreach ($parts['cols'] as $j => $c) {
            $carry = $c['sat'] ?? $carry;
            $odd = false;
            $cc = $mode === 'usd' ? 'USD' : 'IDR';
            $val = amountToInt($c['amt'], $cc, $odd);
            $satuan = $c['sat'] ?? ($spec['sat'] ?? $carry);
            $kolomName = $spec['cols'][$j];
            $regionTxt = $region ? ucwords(mb_strtolower($region)) : null;
            if ($isProv) {
                $kelompok = $spec['name'] . ($sub ? ' — ' . $sub : '');
                $kolom = $kolomName;
                $subU = $kelompok . ' · ' . $kolom;
            } elseif ($n === 1) {
                $kelompok = $spec['name'] . ($regionTxt && empty($spec['transport']) && $spec['wil'] !== 'region' ? ' — ' . $regionTxt : '');
                $kolom = $label;
                $subU = $kelompok . ' · ' . $label;
            } else {
                $kelompok = $label;
                $kolom = $kolomName;
                $subU = ($regionTxt ? $regionTxt . ' · ' : '') . $spec['name'] . ' · ' . $label . ' · ' . $kolomName;
            }
            $rows[] = [
                'kelompok' => $kelompok, 'kolom' => $kolom, 'sub' => $subU, 'wil' => $wilId,
                'satuan' => cleanSat($satuan),
                'besaran' => $val, 'cur' => $cc, 'p' => $bufPage,
                'review' => $odd ? 1 : 0,
                'ket' => $val === null ? AMT_TXT : ($odd ? 'Penulisan angka pada PDF tidak lazim (titik sebagai pemisah ribuan); periksa.' : null),
                'no' => $rowNo,
            ];
        }
    }
    $flush();

    if (isset($spec['expect']) && $spec['expect'] > 0 && ($spec['wil'] ?? '') === 'prov') {
        $provRows = count(array_unique(array_filter(array_column($rows, 'wil'), static fn ($w) => $w !== null)));
        if ($provRows !== $spec['expect']) {
            Report::w("$itemTag jumlah provinsi {$provRows} != {$spec['expect']} (" . $spec['name'] . ')');
        }
    }
    return $rows;
}

/** Daftar bertingkat (honorarium, dst.). */
function parseList(array $lines, string $itemTag, string $itemName): array
{
    $markers = [
        ['/^(\d+(?:\.\d+)+)(?:\s|$)/u', null],
        ['/^([a-z])\.\s/u', 10],
        ['/^(\d+)\)\s/u', 11],
        ['/^([a-z])\)\s/u', 12],
        ['/^\((\d+)\)\s/u', 13],
    ];
    $sat = satuanRegex();
    $blocks = [];
    $blk = null;
    foreach ($lines as $ln) {
        $t = $ln['t'];
        if (isNoise($t)) {
            continue;
        }
        $level = null;
        foreach ($markers as [$re, $lv]) {
            if (preg_match($re, $t, $m)) {
                $level = $lv ?? (substr_count($m[1], '.') + 1);
                break;
            }
        }
        if ($level !== null) {
            if ($blk) {
                $blocks[] = $blk;
            }
            $blk = ['level' => $level, 'text' => $t, 'p' => $ln['p']];
        } elseif ($blk) {
            $blk['text'] .= ' ' . $t;
        } else {
            $blk = ['level' => 0, 'text' => $t, 'p' => $ln['p']];
        }
    }
    if ($blk) {
        $blocks[] = $blk;
    }

    $stack = [];
    $rows = [];
    $amtRe = '(?:' . AMT_RP . '|\$\s?\d{1,3}(?:,\d{3})*|' . AMT_TXT . ')';
    foreach ($blocks as $b) {
        $text = preg_replace(['/Maha\s+siswa/u', '/\bS\s+emester/u', '/\bSe\s+mester/u'], ['Mahasiswa', 'Semester', 'Semester'], $b['text']);
        $text = preg_replace('/\s*\/\s*/u', '/', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        foreach (array_keys($stack) as $lv) {
            if ($lv >= $b['level']) {
                unset($stack[$lv]);
            }
        }
        if (preg_match('/^(.*?)(?:\s+|^)(?:(' . $sat . ')\s+)?(' . $amtRe . ')\s*$/u', $text, $m, PREG_UNMATCHED_AS_NULL)) {
            $label = trim($m[1]);
            $satuan = $m[2];
            $cur = 'IDR';
            $odd = false;
            $val = amountToInt(trim($m[3]), $cur, $odd);
            $review = 0;
            if ($satuan === null && $val !== null) {
                $review = 1;
                Report::w("$itemTag satuan tidak dikenali: $text");
            }
            $kelompok = implode(' › ', $stack);
            $rows[] = [
                'kelompok' => $kelompok,
                'kolom' => $label !== '' ? $label : 'Besaran',
                'sub' => ($kelompok !== '' ? $kelompok . ' › ' : '') . $label,
                'wil' => null,
                'satuan' => cleanSat($satuan),
                'besaran' => $val, 'cur' => $cur, 'p' => $b['p'], 'review' => $review,
                'ket' => $val === null ? AMT_TXT : null, 'no' => '',
            ];
        } else {
            $stack[$b['level']] = $text;
            ksort($stack);
        }
    }
    return $rows;
}
