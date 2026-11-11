<?php

namespace App\Services;

use App\Models\IkpaModel;
use RuntimeException;
use Smalot\PdfParser\Parser;

class IkpaPdfParser
{
    public function parse(string $path): array
    {
        $text = (new Parser())->parseFile($path)->getText();
        if (stripos($text, 'INDIKATOR PELAKSANAAN ANGGARAN SATKER') === false) {
            throw new RuntimeException('PDF bukan laporan Indikator Pelaksanaan Anggaran Satker Kemenkeu.');
        }

        $periodMonth = $this->extractMonth($text);
        $year = $this->extractYear($text);
        $marker = strripos($text, "NILAI AKHIR");
        if ($periodMonth === null || $year === null || $marker === false) {
            throw new RuntimeException('Periode atau tabel nilai tidak ditemukan pada PDF.');
        }

        $valueText = substr($text, $marker + strlen('NILAI AKHIR'));
        preg_match_all('/\d{1,3},\d{2}%?|(?<![\d,])\d{1,3}(?![\d,])/', $valueText, $matches);
        $tokens = array_map(static fn(string $value): float => (float)str_replace([',', '%'], ['.', ''], $value), $matches[0]);

        // Urutan baku laporan DJPb: nilai, bobot, nilai akhir per indikator; nilai aspek di antaranya.
        $positions = ['revisi' => 0, 'deviasi' => 3, 'penyerapan' => 7, 'kontraktual' => 10, 'tagihan' => 13, 'uptup' => 16, 'output' => 20];
        if (count($tokens) < 28) {
            throw new RuntimeException('Susunan angka dalam PDF tidak lengkap atau formatnya telah berubah.');
        }

        $data = [];
        foreach ($positions as $key => $position) {
            $data[$key] = $tokens[$position];
        }
        $data['dispensasi'] = $tokens[26];
        $officialScore = $tokens[27];
        $calculated = IkpaModel::calculateScore($data);
        if (!$calculated || abs($calculated['score'] - $officialScore) > 0.06) {
            throw new RuntimeException('Nilai hasil hitung aplikasi tidak cocok dengan nilai akhir PDF. Impor dibatalkan untuk mencegah data keliru.');
        }

        return [
            'year' => $year,
            'period_month' => $periodMonth,
            'values' => $data,
            'official_score' => $officialScore,
            'calculated_score' => $calculated['score'],
            'text' => $text,
        ];
    }

    private function extractMonth(string $text): ?int
    {
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        foreach ($months as $number => $name) {
            if (preg_match('/\b' . preg_quote($name, '/') . '\b/i', $text)) {
                return $number;
            }
        }
        return null;
    }

    private function extractYear(string $text): ?int
    {
        if (preg_match('/tanggal cetak\s*:\s*\d{1,2}-\d{1,2}-(\d{2,4})/i', $text, $match)) {
            $year = (int)$match[1];
            return $year < 100 ? 2000 + $year : $year;
        }
        return null;
    }
}
