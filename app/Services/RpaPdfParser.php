<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;

class RpaPdfParser
{
    /**
     * Parse an RPA PDF document and extract metadata, monthly totals, and line items.
     *
     * @param string $path Path to the PDF file.
     * @return array
     */
    public function parse(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File PDF tidak ditemukan: {$path}");
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        // Suppress deprecation warnings from third-party PDF parser if any
        $prevErrorReporting = error_reporting();
        error_reporting($prevErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            $pages = $pdf->getPages();

            if (empty($pages)) {
                throw new RuntimeException("File PDF tidak memiliki halaman yang dapat dibaca.");
            }

            $byColumns = $this->parseByColumns($pages);
            if ($byColumns !== null) {
                return $byColumns;
            }

            // Cadangan: tata letak lama dengan batas koordinat tetap.
            $allRows = [];
            $grandTotal = 0.0;
            $monthlyTotals = array_fill(1, 12, 0.0);

            foreach ($pages as $pIdx => $page) {
                $data = $page->getDataTm();

                // Collect all text items with X, Y coordinates
                $items = [];
                foreach ($data as $d) {
                    $txt = trim($d[1]);
                    if ($txt === '') continue;
                    $items[] = [
                        'x' => (float)$d[0][4],
                        'y' => (float)$d[0][5],
                        'text' => $txt,
                    ];
                }

                // Sort primarily descending by Y (top of page first), then ascending by X
                usort($items, function ($a, $b) {
                    if (abs($a['y'] - $b['y']) > 6.0) {
                        return $b['y'] <=> $a['y'];
                    }
                    return $a['x'] <=> $b['x'];
                });

                // Cluster into rows
                $rows = [];
                $currentRow = null;

                foreach ($items as $item) {
                    if ($currentRow === null) {
                        $currentRow = ['y' => $item['y'], 'items' => [$item]];
                        continue;
                    }

                    // Belongs to same row if vertical distance is small
                    if (abs($currentRow['y'] - $item['y']) <= 7.0) {
                        $currentRow['items'][] = $item;
                        $currentRow['y'] = ($currentRow['y'] + $item['y']) / 2;
                    } else {
                        // Check if text item in RINCIAN column is a continuation line of the current row
                        if ($item['x'] >= 95 && $item['x'] < 435 && abs($currentRow['y'] - $item['y']) <= 11.0) {
                            $currentRow['items'][] = $item;
                            continue;
                        }

                        $rows[] = $currentRow;
                        $currentRow = ['y' => $item['y'], 'items' => [$item]];
                    }
                }
                if ($currentRow !== null) {
                    $rows[] = $currentRow;
                }

                // Parse each cluster
                foreach ($rows as $r) {
                    $parsed = $this->parseRowCluster($r['items'], $pIdx + 1, $r['y']);
                    if ($parsed === null) continue;

                    // Check for grand total line
                    if (stripos($parsed['name'], 'JUMLAH KESELURUHAN') !== false ||
                        ($pIdx === 0 && empty($parsed['name']) && empty($parsed['code']) && $parsed['ceiling'] > 0)) {
                        $grandTotal = $parsed['ceiling'] > 0 ? $parsed['ceiling'] : $parsed['total'];
                        $monthlyTotals = $parsed['months'];
                        continue;
                    }

                    // Skip headers and empty rows
                    if (stripos($parsed['name'], 'RENCANA PENARIKAN') !== false ||
                        stripos($parsed['name'], 'RINCIAN') !== false ||
                        $parsed['code'] === 'KODE' || $parsed['code'] === '1' ||
                        (empty($parsed['code']) && empty($parsed['name']) && $parsed['ceiling'] == 0 && array_sum($parsed['months']) == 0)) {
                        continue;
                    }

                    $allRows[] = $parsed;
                }
            }

            // Post-process: merge wrapped rincian lines
            $mergedRows = [];
            $count = count($allRows);
            for ($i = 0; $i < $count; $i++) {
                $curr = $allRows[$i];
                $isTextOnly = empty($curr['code']) && $curr['ceiling'] == 0 && array_sum($curr['months']) == 0;

                if ($isTextOnly && isset($allRows[$i + 1])) {
                    $next = &$allRows[$i + 1];
                    if (!empty($next['code']) || $next['ceiling'] > 0 || array_sum($next['months']) > 0) {
                        $next['name'] = trim($curr['name'] . ' ' . $next['name']);
                        continue;
                    }
                } elseif ($isTextOnly && count($mergedRows) > 0) {
                    $lastIdx = count($mergedRows) - 1;
                    $mergedRows[$lastIdx]['name'] = trim($mergedRows[$lastIdx]['name'] . ' ' . $curr['name']);
                    continue;
                }
                $mergedRows[] = $curr;
            }

            return [
                'grand_total' => $grandTotal ?: (float)array_sum(array_map(
                    static fn ($r) => str_starts_with($r['code'], '060.01.') ? $r['ceiling'] : 0,
                    $mergedRows
                )),
                'monthly_totals' => $monthlyTotals,
                'rows' => $mergedRows,
            ];
        } finally {
            error_reporting($prevErrorReporting);
        }
    }


    /**
     * Parser berbasis posisi kolom (format RPA hasil cetak Excel/CAD: KODE, RINCIAN, JUMLAH, Jan..Des, JUMLAH).
     * Batas kolom diturunkan dari baris JUMLAH KESELURUHAN di bagian atas halaman 1, jadi tidak bergantung
     * pada angka koordinat tetap. Mengembalikan null bila tata letak tidak dikenali.
     */
    private function parseByColumns(array $pages): ?array
    {
        $tokensByPage = [];
        foreach ($pages as $pIdx => $page) {
            $list = [];
            foreach ($page->getDataTm() as $d) {
                $txt = trim((string)$d[1]);
                if ($txt === '') {
                    continue;
                }
                $list[] = ['x' => (float)$d[0][4], 'y' => (float)$d[0][5], 'text' => $txt];
            }
            $tokensByPage[$pIdx] = $list;
        }
        $first = reset($tokensByPage);
        if (!$first) {
            return null;
        }

        // Baris judul pada halaman 1 (KODE ... 18) dilewati.
        $yKode = null;
        foreach ($first as $t) {
            if ($t['text'] === 'KODE') {
                $yKode = $t['y'];
                break;
            }
        }
        $headerLimit = $yKode !== null ? $yKode - 15.0 : null;

        // Tepi kanan tiap kolom angka dari baris total teratas (14 kolom: JUMLAH, 12 bulan, JUMLAH).
        $topNums = [];
        foreach ($first as $t) {
            if ($headerLimit !== null && $t['y'] > $headerLimit) {
                continue;
            }
            if ($t['x'] >= 200 && $this->isNumberToken($t['text'])) {
                $topNums[] = $t;
            }
        }
        if ($topNums === []) {
            return null;
        }
        usort($topNums, static fn ($a, $b) => $b['y'] <=> $a['y']);
        $topY = $topNums[0]['y'];
        $topRow = array_values(array_filter($topNums, static fn ($t) => abs($t['y'] - $topY) <= 3.0));
        if (count($topRow) !== 14) {
            return null;
        }
        usort($topRow, static fn ($a, $b) => $a['x'] <=> $b['x']);
        $rights = [];
        foreach ($topRow as $t) {
            $rights[] = $t['x'] + $this->textWidth($t['text']);
        }

        $rows = [];
        $grandTotal = 0.0;
        $monthlyTotals = array_fill(1, 12, 0.0);

        foreach ($tokensByPage as $pIdx => $tokens) {
            $anchors = [];
            $texts = [];
            foreach ($tokens as $t) {
                if ($pIdx === 0 && $headerLimit !== null && $t['y'] > $headerLimit) {
                    continue;
                }
                if ($t['x'] < 50) {
                    $anchors[] = $t + ['kind' => 'code'];
                } elseif ($t['x'] < 200) {
                    $texts[] = $t;
                } elseif ($this->isNumberToken($t['text'])) {
                    $anchors[] = $t + ['kind' => 'num'];
                }
            }
            if ($anchors === [] && $texts === []) {
                continue;
            }

            usort($anchors, static fn ($a, $b) => $b['y'] <=> $a['y']);
            $clusters = [];
            foreach ($anchors as $a) {
                $last = count($clusters) - 1;
                if ($last >= 0 && ($clusters[$last]['minY'] - $a['y']) <= 3.5) {
                    $clusters[$last]['items'][] = $a;
                    $clusters[$last]['minY'] = min($clusters[$last]['minY'], $a['y']);
                } else {
                    $clusters[] = ['minY' => $a['y'], 'items' => [$a], 'names' => []];
                }
            }
            foreach ($clusters as &$c) {
                $c['y'] = array_sum(array_column($c['items'], 'y')) / count($c['items']);
            }
            unset($c);

            // Teks rincian dipasangkan ke baris terdekat (mendukung nama yang terbungkus beberapa baris).
            foreach ($texts as $t) {
                $best = null;
                $bestD = 1e9;
                foreach ($clusters as $i => $c) {
                    $d = abs($c['y'] - $t['y']);
                    if ($d < $bestD) {
                        $bestD = $d;
                        $best = $i;
                    }
                }
                if ($best !== null && $bestD <= 9.0) {
                    $clusters[$best]['names'][] = $t;
                }
            }

            foreach ($clusters as $c) {
                $codeParts = [];
                $months = array_fill(1, 12, 0.0);
                $ceiling = 0.0;
                $total = 0.0;
                $hasNum = false;
                $placed = [];
                usort($c['items'], static fn ($a, $b) => $a['x'] <=> $b['x']);
                foreach ($c['items'] as $it) {
                    if ($it['kind'] === 'code') {
                        $codeParts[] = $it['text'];
                        continue;
                    }
                    $val = $this->num($it['text']);
                    if ($val === null) {
                        continue;
                    }
                    $hasNum = true;
                    if ($val == 0.0) {
                        continue;   // tanda "-" / 0 tidak boleh menimpa nilai pada kolom yang sama
                    }
                    $right = $it['x'] + $this->textWidth($it['text']);
                    if ($right > end($rights) + 20.0) {
                        continue;   // angka di luar tabel (catatan/sisa perhitungan di margin kanan)
                    }
                    $col = 0;
                    $bestD = 1e9;
                    foreach ($rights as $i => $r) {
                        $d = abs($r - $right);
                        if ($d < $bestD) {
                            $bestD = $d;
                            $col = $i;
                        }
                    }
                    $placed[] = ['col' => $col, 'val' => $val];
                }

                // Satu angka per kolom: bila dua angka jatuh di kolom yang sama, urutan kiri-kanan dipertahankan.
                for ($i = 1, $n = count($placed); $i < $n; $i++) {
                    if ($placed[$i]['col'] <= $placed[$i - 1]['col']) {
                        $placed[$i]['col'] = $placed[$i - 1]['col'] + 1;
                    }
                }
                $limit = 13;
                for ($i = count($placed) - 1; $i >= 0; $i--) {
                    if ($placed[$i]['col'] > $limit) {
                        $placed[$i]['col'] = $limit;
                    }
                    $limit = $placed[$i]['col'] - 1;
                }
                foreach ($placed as $p) {
                    if ($p['col'] === 0) {
                        $ceiling = $p['val'];
                    } elseif ($p['col'] === 13) {
                        $total = $p['val'];
                    } else {
                        $months[$p['col']] = $p['val'];
                    }
                }

                usort($c['names'], static fn ($a, $b) => abs($a['y'] - $b['y']) > 1.6 ? $b['y'] <=> $a['y'] : $a['x'] <=> $b['x']);
                $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_column($c['names'], 'text'))));
                $code = trim(implode(' ', $codeParts));

                if ($code === '' && $name === '' && !$hasNum) {
                    continue;
                }
                if (stripos($name, 'JUMLAH KESELURUHAN') !== false || ($code === '' && $name === '' && $hasNum && $grandTotal == 0.0 && $ceiling > 0)) {
                    $grandTotal = $ceiling > 0 ? $ceiling : $total;
                    $monthlyTotals = $months;
                    continue;
                }

                if ($code === '' && $name === '') {
                    continue;   // sisa sel kosong
                }

                $rows[] = [
                    'page' => $pIdx + 1,
                    'y' => $c['y'],
                    'code' => $code,
                    'name' => $name,
                    'ceiling' => $ceiling,
                    'months' => $months,
                    'total' => $total ?: array_sum($months),
                ];
            }
        }

        if ($rows === []) {
            return null;
        }

        return [
            'grand_total' => $grandTotal ?: (float)array_sum(array_map(
                static fn ($r) => str_starts_with($r['code'], '060.01.') ? $r['ceiling'] : 0,
                $rows
            )),
            'monthly_totals' => $monthlyTotals,
            'rows' => $rows,
        ];
    }

    private function isNumberToken(string $txt): bool
    {
        return (bool)preg_match('/^\d{1,3}(?:\.\d{3})*$|^\d+$|^-$/', $txt);
    }

    /** Perkiraan lebar teks (Arial 6 pt): digit 3,34; titik 1,67; tanda minus 2. */
    private function textWidth(string $txt): float
    {
        $w = 0.0;
        foreach (str_split($txt) as $ch) {
            $w += $ch === '.' ? 1.67 : ($ch === '-' ? 2.0 : 3.34);
        }
        return $w;
    }


    /**
     * Parse items in a single row cluster into columns.
     */
    private function parseRowCluster(array $items, int $page, float $y): ?array
    {
        usort($items, fn($a, $b) => $a['x'] <=> $b['x']);

        $codeParts = [];
        $nameParts = [];
        $ceiling = 0.0;
        $months = array_fill(1, 12, 0.0);
        $total = 0.0;

        foreach ($items as $item) {
            $x = $item['x'];
            $txt = $item['text'];

            if ($x < 95) {
                $codeParts[] = $txt;
            } elseif ($x < 435) {
                $nameParts[] = $txt;
            } elseif ($x < 545) {
                $val = $this->num($txt);
                if ($val !== null) $ceiling = $val;
            } elseif ($x >= 1890) {
                $val = $this->num($txt);
                if ($val !== null) $total = $val;
            } else {
                $mIndex = $this->getMonthIndex($x);
                if ($mIndex >= 1 && $mIndex <= 12) {
                    $val = $this->num($txt);
                    if ($val !== null) $months[$mIndex] = $val;
                }
            }
        }

        $code = trim(implode(' ', $codeParts));
        $name = trim(implode(' ', $nameParts));

        // Format code if merged with name
        if (preg_match('/^([A-Z0-9.\-]+)\s+(.+)$/i', $code, $m) && empty($name)) {
            $code = $m[1];
            $name = $m[2];
        }

        return [
            'page' => $page,
            'y' => $y,
            'code' => $code,
            'name' => $name,
            'ceiling' => $ceiling,
            'months' => $months,
            'total' => $total ?: array_sum($months),
        ];
    }

    /**
     * Map X coordinate to month index 1..12.
     */
    private function getMonthIndex(float $x): int
    {
        if ($x >= 545 && $x < 655) return 1;
        if ($x >= 655 && $x < 765) return 2;
        if ($x >= 765 && $x < 875) return 3;
        if ($x >= 875 && $x < 985) return 4;
        if ($x >= 985 && $x < 1105) return 5;
        if ($x >= 1105 && $x < 1215) return 6;
        if ($x >= 1215 && $x < 1325) return 7;
        if ($x >= 1325 && $x < 1445) return 8;
        if ($x >= 1445 && $x < 1560) return 9;
        if ($x >= 1560 && $x < 1670) return 10;
        if ($x >= 1670 && $x < 1780) return 11;
        if ($x >= 1780 && $x < 1890) return 12;
        return 0;
    }

    /**
     * Clean numeric string to float.
     */
    private function num(string $val): ?float
    {
        $v = trim($val);
        if ($v === '' || $v === '-') return 0.0;
        $v = str_replace([',', '.'], '', $v);
        return is_numeric($v) ? (float)$v : null;
    }
}
