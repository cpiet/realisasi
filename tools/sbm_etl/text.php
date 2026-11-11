<?php
/** Pembersih teks penjelasan hasil ekstraksi PDF. */

function cleanLines(array $stream): array
{
    return array_values(array_filter($stream, static fn ($l) => !($l['t'] === '' || preg_match('/^- \d+ -$/', $l['t']) || preg_match('/^\d{1,3}$/', $l['t']))));
}

/** Gabungkan baris menjadi paragraf; butir (a., 1), dst.) dan sub-judul memulai paragraf baru. */
function paragraphs(array $lines): array
{
    $paras = [];
    $cur = '';
    $curLines = 0;
    $marker = '/^(?:\d+(?:\.\d+)+\.?\s|[a-z]\.\s|\d+\)\s|[a-z]\)\s|\d+\.\s|•|Ketentuan:|Catatan:|Contoh|Keterangan:|Narasumber Kelas|Klasifikasi)/u';
    foreach ($lines as $l) {
        $t = $l['t'];
        if ($t === '') {
            continue;
        }
        $isHeading = $cur !== '' && $curLines === 1 && preg_match('/^\d+(?:\.\d+)+\.?\s+\S/u', $cur)
            && mb_strlen($cur) < 110 && !preg_match('/[.:;,]$/u', $cur) && preg_match('/^\p{Lu}/u', $t);
        if ($cur !== '' && (preg_match($marker, $t) || $isHeading)) {
            $paras[] = $cur;
            $cur = $t;
            $curLines = 1;
        } elseif ($cur === '') {
            $cur = $t;
            $curLines = 1;
        } elseif (str_ends_with($cur, '-') && !str_ends_with($cur, ' -')) {
            $cur .= $t;
            $curLines++;
        } else {
            $cur .= ' ' . $t;
            $curLines++;
        }
    }
    if ($cur !== '') {
        $paras[] = $cur;
    }
    return array_map(static fn ($p) => preg_replace('/\s*\/\s*/u', '/', preg_replace('/\s+/u', ' ', preg_replace('/\s+([,.;:)])/u', '$1', $p))), $paras);
}

/** Kosakata dari seluruh teks dokumen (kata huruf, frekuensi). */
function buildVocab(array $pages): array
{
    $v = [];
    foreach ($pages as $lines) {
        foreach ($lines as $l) {
            if (preg_match_all('/\p{L}{2,}/u', mb_strtolower($l), $m)) {
                foreach ($m[0] as $w) {
                    $v[$w] = ($v[$w] ?? 0) + 1;
                }
            }
        }
    }
    return $v;
}

/** Sambung kata yang terpotong spasi saat ekstraksi ("Kete rangan" -> "Keterangan"). */
function fixSplits(string $text, array $vocab): string
{
    $stop = ['di', 'ke', 'dan', 'yang', 'atau', 'dari', 'pada', 'ini', 'itu', 'ada', 'oleh', 'bagi', 'dalam'];
    return preg_replace_callback('/\b(\p{L}{1,8}) (\p{L}{2,12})\b/u', static function ($m) use ($vocab, $stop) {
        $a = mb_strtolower($m[1]);
        $b = mb_strtolower($m[2]);
        $fa = $vocab[$a] ?? 0;
        $fb = $vocab[$b] ?? 0;
        $fab = $vocab[$a . $b] ?? 0;
        if ($fab >= 2 && ($fa < 3 || $fb < 3) && !in_array($a, $stop, true)) {
            return $m[1] . $m[2];
        }
        return $m[0];
    }, $text) ?? $text;
}
